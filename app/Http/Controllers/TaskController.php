<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ParsesScalarVariables;
use App\Models\ProcessDefinition;
use App\Models\User;
use App\Services\Operaton\OperatonClient;
use App\Services\Operaton\OperatonException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TaskController extends Controller
{
    use ParsesScalarVariables;

    public function index(Request $request, OperatonClient $operaton): View
    {
        $status = $operaton->status();
        $selectedInstanceId = trim((string) $request->query('instance'));
        $selectedDefinitionId = trim((string) $request->query('definition'));
        $scope = trim((string) $request->query('scope', 'all'));
        $currentAssignee = $request->user()->operatonAssignee();
        $tasks = [];
        $linkedDefinitions = collect();
        $availableTaskCount = 0;

        if ($status['ok']) {
            try {
                $tasks = $operaton->tasks(
                    processInstanceId: $selectedInstanceId !== '' ? $selectedInstanceId : null,
                    processDefinitionId: $selectedDefinitionId !== '' ? $selectedDefinitionId : null,
                );

                $availableTaskCount = count($tasks);
                $tasks = $this->filterTasksForScope($tasks, $scope, $currentAssignee);
                $linkedDefinitions = $this->definitionsForTasks($tasks);
            } catch (OperatonException $exception) {
                $status['ok'] = false;
                $status['message'] = $exception->getMessage();
            }
        }

        return view('tasks.index', [
            'status' => $status,
            'tasks' => $tasks,
            'linkedDefinitions' => $linkedDefinitions,
            'selectedInstanceId' => $selectedInstanceId,
            'selectedDefinitionId' => $selectedDefinitionId,
            'scope' => in_array($scope, ['all', 'mine', 'unassigned'], true) ? $scope : 'all',
            'currentAssignee' => $currentAssignee,
            'availableTaskCount' => $availableTaskCount,
        ]);
    }

    public function show(string $taskId, OperatonClient $operaton): View|RedirectResponse
    {
        try {
            $task = $operaton->task($taskId);
        } catch (OperatonException $exception) {
            return redirect()
                ->route('tasks.index')
                ->withErrors([
                    'task' => $exception->getMessage(),
                ]);
        }

        $taskFormVariables = [];
        $taskFormError = null;

        try {
            $taskFormVariables = $operaton->taskFormVariables($taskId);
        } catch (OperatonException $exception) {
            $taskFormError = $exception->getMessage();
        }

        $linkedDefinition = filled($task['processDefinitionId'] ?? null)
            ? ProcessDefinition::query()
                ->where('engine_process_definition_id', $task['processDefinitionId'])
                ->first()
            : null;

        return view('tasks.show', [
            'task' => $task,
            'linkedDefinition' => $linkedDefinition,
            'currentAssignee' => auth()->user()->operatonAssignee(),
            'taskFormVariables' => $taskFormVariables,
            'taskFormError' => $taskFormError,
        ]);
    }

    public function claim(
        Request $request,
        string $taskId,
        OperatonClient $operaton,
    ): RedirectResponse {
        $user = $request->user();

        try {
            $task = $operaton->task($taskId);

            if ($this->taskOwnedByCurrentUser($task, $user)) {
                return back()->with('status', 'This task is already assigned to you.');
            }

            if ($this->taskOwnedByAnotherUser($task, $user)) {
                return back()->withErrors([
                    'task' => 'This task is already claimed by another user.',
                ]);
            }

            $operaton->claimTask($taskId, $user->operatonAssignee());
        } catch (OperatonException $exception) {
            return back()->withErrors([
                'task' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', 'Task claimed successfully.');
    }

    public function release(
        Request $request,
        string $taskId,
        OperatonClient $operaton,
    ): RedirectResponse {
        $user = $request->user();

        try {
            $task = $operaton->task($taskId);
        } catch (OperatonException $exception) {
            return back()->withErrors([
                'task' => $exception->getMessage(),
            ]);
        }

        if (! $this->taskOwnedByCurrentUser($task, $user)) {
            if (blank($task['assignee'] ?? null)) {
                return back()->with('status', 'This task is already unassigned.');
            }

            return back()->withErrors([
                'task' => 'Only the current task assignee can release this task.',
            ]);
        }

        try {
            $operaton->unclaimTask($taskId);
        } catch (OperatonException $exception) {
            return back()->withErrors([
                'task' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', 'Task released successfully.');
    }

    public function complete(
        Request $request,
        string $taskId,
        OperatonClient $operaton,
    ): RedirectResponse {
        $validated = $request->validate([
            'variables_json' => ['nullable', 'string'],
            'form_values' => ['sometimes', 'array'],
        ]);

        try {
            $task = $operaton->task($taskId);

            if ($this->taskOwnedByAnotherUser($task, $request->user())) {
                return back()->withErrors([
                    'task' => 'This task is assigned to another user and cannot be completed from your session.',
                ])->withInput();
            }

            $jsonVariables = $this->parseScalarVariablesJson(
                $validated['variables_json'] ?? null,
                field: 'variables_json',
                label: 'Completion variables',
            );

            if ($request->has('form_values')) {
                $taskFormVariables = $operaton->taskFormVariables($taskId);
                $formVariables = $this->parseSubmittedTaskFormValues(
                    submittedValues: $request->input('form_values', []),
                    taskFormVariables: $taskFormVariables,
                );

                $operaton->submitTaskForm($taskId, array_replace($formVariables, $jsonVariables));
            } else {
                $operaton->completeTask($taskId, $jsonVariables);
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (OperatonException $exception) {
            return back()->withErrors([
                'task' => $exception->getMessage(),
            ])->withInput();
        }

        if (filled($task['processInstanceId'] ?? null)) {
            return redirect()
                ->route('runtime.instances.show', $task['processInstanceId'])
                ->with('status', 'Task completed successfully.');
        }

        return redirect()
            ->route('tasks.index')
            ->with('status', 'Task completed successfully.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $tasks
     * @return Collection<string, ProcessDefinition>
     */
    private function definitionsForTasks(array $tasks): Collection
    {
        $definitionIds = collect($tasks)
            ->pluck('processDefinitionId')
            ->filter()
            ->unique()
            ->values();

        if ($definitionIds->isEmpty()) {
            return collect();
        }

        return ProcessDefinition::query()
            ->whereIn('engine_process_definition_id', $definitionIds)
            ->get()
            ->keyBy('engine_process_definition_id');
    }

    /**
     * @param  array<int, array<string, mixed>>  $tasks
     * @return array<int, array<string, mixed>>
     */
    private function filterTasksForScope(array $tasks, string $scope, string $currentAssignee): array
    {
        return match ($scope) {
            'mine' => array_values(array_filter(
                $tasks,
                fn (array $task): bool => ($task['assignee'] ?? null) === $currentAssignee,
            )),
            'unassigned' => array_values(array_filter(
                $tasks,
                fn (array $task): bool => blank($task['assignee'] ?? null),
            )),
            default => $tasks,
        };
    }

    /**
     * @param  array<string, mixed>  $task
     */
    private function taskOwnedByCurrentUser(array $task, User $user): bool
    {
        return ($task['assignee'] ?? null) === $user->operatonAssignee();
    }

    /**
     * @param  array<string, mixed>  $task
     */
    private function taskOwnedByAnotherUser(array $task, User $user): bool
    {
        $assignee = $task['assignee'] ?? null;

        return filled($assignee) && $assignee !== $user->operatonAssignee();
    }

    /**
     * @param  array<string, mixed>  $submittedValues
     * @param  array<string, array<string, mixed>>  $taskFormVariables
     * @return array<string, bool|int|float|string|null>
     */
    private function parseSubmittedTaskFormValues(array $submittedValues, array $taskFormVariables): array
    {
        $normalized = [];

        foreach ($taskFormVariables as $name => $metadata) {
            $rawValue = $submittedValues[$name] ?? null;
            $normalized[$name] = $this->castTaskFormValue(
                name: $name,
                rawValue: $rawValue,
                type: (string) ($metadata['type'] ?? 'String'),
            );
        }

        return $normalized;
    }

    /**
     * @param  mixed  $rawValue
     * @return bool|int|float|string|null
     */
    private function castTaskFormValue(string $name, mixed $rawValue, string $type): bool|int|float|string|null
    {
        if ($rawValue === null || $rawValue === '') {
            return null;
        }

        return match (strtolower($type)) {
            'boolean' => match ($rawValue) {
                true, 'true', '1', 1, 'on' => true,
                false, 'false', '0', 0 => false,
                default => throw ValidationException::withMessages([
                    "form_values.$name" => "The [$name] field must be a valid boolean value.",
                ]),
            },
            'integer', 'long' => filter_var($rawValue, FILTER_VALIDATE_INT) !== false
                ? (int) $rawValue
                : throw ValidationException::withMessages([
                    "form_values.$name" => "The [$name] field must be a valid integer.",
                ]),
            'double' => is_numeric($rawValue)
                ? (float) $rawValue
                : throw ValidationException::withMessages([
                    "form_values.$name" => "The [$name] field must be a valid number.",
                ]),
            default => (string) $rawValue,
        };
    }

}
