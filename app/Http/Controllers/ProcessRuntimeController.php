<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ParsesScalarVariables;
use App\Models\ProcessDefinition;
use App\Services\Operaton\OperatonClient;
use App\Services\Operaton\OperatonException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProcessRuntimeController extends Controller
{
    use ParsesScalarVariables;

    public function index(Request $request, OperatonClient $operaton): View
    {
        $status = $operaton->status();
        $deployedDefinitions = ProcessDefinition::query()
            ->with('creator')
            ->whereNotNull('engine_process_definition_id')
            ->orderBy('process_key')
            ->orderByDesc('version')
            ->get();

        $selectedDefinitionId = trim((string) $request->query('definition'));
        $selectedDefinition = $selectedDefinitionId !== ''
            ? $deployedDefinitions->firstWhere('engine_process_definition_id', $selectedDefinitionId)
            : null;

        $instances = [];

        if ($status['ok']) {
            try {
                $instances = $operaton->recentProcessInstances(
                    processDefinitionId: $selectedDefinition?->engine_process_definition_id ?? null,
                );
            } catch (OperatonException $exception) {
                $status['ok'] = false;
                $status['message'] = $exception->getMessage();
            }
        }

        return view('runtime.index', [
            'status' => $status,
            'deployedDefinitions' => $deployedDefinitions,
            'selectedDefinition' => $selectedDefinition,
            'instances' => $instances,
        ]);
    }

    public function start(
        Request $request,
        ProcessDefinition $processDefinition,
        OperatonClient $operaton,
    ): RedirectResponse {
        $validated = $request->validate([
            'business_key' => ['nullable', 'string', 'max:120'],
            'variables_json' => ['nullable', 'string'],
        ]);

        if (! $processDefinition->isPublished()) {
            return back()->withErrors([
                'runtime' => 'Only the published process version can be started from Laravel in this stage.',
            ]);
        }

        if (! $processDefinition->isDeployed()) {
            return back()->withErrors([
                'runtime' => 'Deploy this process definition to Operaton before starting instances.',
            ]);
        }

        $startVariables = $this->parseScalarVariablesJson(
            $validated['variables_json'] ?? null,
            field: 'variables_json',
            label: 'Start variables',
        );

        try {
            $instance = $operaton->startProcessInstance(
                definition: $processDefinition,
                businessKey: $validated['business_key'] ?? null,
                variables: $startVariables,
            );
        } catch (OperatonException $exception) {
            return back()->withErrors([
                'runtime' => $exception->getMessage(),
            ])->withInput();
        }

        if (! filled($instance['id'] ?? null)) {
            return back()->withErrors([
                'runtime' => 'Operaton did not return a process instance ID after start.',
            ]);
        }

        return redirect()
            ->route('runtime.instances.show', $instance['id'])
            ->with('status', 'Process instance started successfully.');
    }

    public function show(string $instanceId, OperatonClient $operaton): View|RedirectResponse
    {
        try {
            $instance = $operaton->processInstance($instanceId);
            $activeTasks = $operaton->activeTasks($instanceId);
        } catch (OperatonException $exception) {
            return redirect()
                ->route('runtime.instances.index')
                ->withErrors([
                    'runtime' => $exception->getMessage(),
                ]);
        }

        $processVariables = [];
        $activityHistory = [];
        $historyWarnings = [];

        try {
            $processVariables = $operaton->processVariables($instanceId);
        } catch (OperatonException $exception) {
            $historyWarnings['variables'] = $exception->getMessage();
        }

        try {
            $activityHistory = $operaton->activityHistory($instanceId);
        } catch (OperatonException $exception) {
            $historyWarnings['activities'] = $exception->getMessage();
        }

        $linkedDefinition = null;

        if (filled($instance['processDefinitionId'] ?? null)) {
            $linkedDefinition = ProcessDefinition::query()
                ->where('engine_process_definition_id', $instance['processDefinitionId'])
                ->first();
        }

        if ($linkedDefinition === null && filled($instance['processDefinitionKey'] ?? null)) {
            $linkedDefinition = ProcessDefinition::query()
                ->where('process_key', $instance['processDefinitionKey'])
                ->orderByDesc('version')
                ->first();
        }

        return view('runtime.show', [
            'instance' => $instance,
            'activeTasks' => $activeTasks,
            'processVariables' => $processVariables,
            'activityHistory' => $activityHistory,
            'historyWarnings' => $historyWarnings,
            'linkedDefinition' => $linkedDefinition,
        ]);
    }
}
