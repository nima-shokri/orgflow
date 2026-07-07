@extends('layouts.app', [
    'title' => 'Task Detail',
    'badge' => 'Stage 09',
    'subtitle' => 'Inspect one live user task, load its form variables, and complete it from Laravel.',
])

@section('content')
    <section class="panel">
        <div class="nav" style="justify-content: space-between;">
            <div>
                <h2>{{ $task['name'] ?? 'Unnamed task' }}</h2>
                <p class="helper">Task ID: <span class="mono">{{ $task['id'] ?? 'n/a' }}</span></p>
            </div>

            <div class="nav">
                <span class="pill">Assignee: {{ $task['assignee'] ?? 'unassigned' }}</span>
                @if (($task['assignee'] ?? null) === $currentAssignee)
                    <span class="pill">Owned by you</span>
                @endif
                @if (! empty($task['priority']))
                    <span class="pill">Priority: {{ $task['priority'] }}</span>
                @endif
            </div>
        </div>

        <div class="meta" style="margin-top: 14px;">
            <span class="pill">Created: {{ $task['created'] ?? 'n/a' }}</span>
            <span class="pill">Due: {{ $task['due'] ?? 'not set' }}</span>
            <span class="pill">Follow-up: {{ $task['followUp'] ?? 'not set' }}</span>
        </div>
    </section>

    <section class="panel">
        <div class="nav">
            <a class="button secondary" href="{{ route('tasks.index') }}">Back to inbox</a>
            @if (! empty($task['processInstanceId']))
                <a class="button secondary" href="{{ route('runtime.instances.show', $task['processInstanceId']) }}">Open runtime instance</a>
            @endif
            @if ($linkedDefinition)
                <a class="button secondary" href="{{ route('process-definitions.show', $linkedDefinition) }}">Open linked definition</a>
            @endif
        </div>
    </section>

    <section class="panel">
        <div class="nav" style="justify-content: space-between;">
            <div>
                <h3>Ownership</h3>
                <p class="helper">Use claim/release to reserve this task before working on it. Completion is blocked only when the task belongs to another user.</p>
            </div>

            <div class="nav">
                @if (blank($task['assignee'] ?? null))
                    <form class="inline-form" method="POST" action="{{ route('tasks.claim', $task['id']) }}">
                        @csrf
                        <button type="submit">Claim for {{ $currentAssignee }}</button>
                    </form>
                @elseif (($task['assignee'] ?? null) === $currentAssignee)
                    <form class="inline-form" method="POST" action="{{ route('tasks.release', $task['id']) }}">
                        @csrf
                        <button type="submit">Release task</button>
                    </form>
                @else
                    <span class="pill">Owned by {{ $task['assignee'] }}</span>
                @endif
            </div>
        </div>
    </section>

    <section class="grid two">
        <div class="panel">
            <h3>Task metadata</h3>
            <div class="stack">
                <p class="helper">Task definition key: <span class="mono">{{ $task['taskDefinitionKey'] ?? 'n/a' }}</span></p>
                <p class="helper">Process instance ID: <span class="mono">{{ $task['processInstanceId'] ?? 'n/a' }}</span></p>
                <p class="helper">Process definition ID: <span class="mono">{{ $task['processDefinitionId'] ?? 'n/a' }}</span></p>
                <p class="helper">Execution ID: <span class="mono">{{ $task['executionId'] ?? 'n/a' }}</span></p>
            </div>
        </div>

        <div class="panel">
            <h3>Linked process</h3>
            @if ($linkedDefinition)
                <div class="stack">
                    <p class="helper">Name: {{ $linkedDefinition->name }}</p>
                    <p class="helper">Process key: <span class="mono">{{ $linkedDefinition->process_key }}</span></p>
                    <p class="helper">Local version: v{{ $linkedDefinition->version }}</p>
                    <p class="helper">Local status: {{ $linkedDefinition->status }}</p>
                </div>
            @else
                <p class="lead">No local process definition record was matched for this task's runtime definition ID.</p>
            @endif
        </div>
    </section>

    @if (! empty($task['description']))
        <section class="panel">
            <h3>Description</h3>
            <p>{{ $task['description'] }}</p>
        </section>
    @endif

    <section class="panel">
        <h3>Complete task</h3>
        <p class="lead">You can finish this user task immediately unless it is assigned to another user. When Operaton exposes form variables, this page now renders them as native Laravel inputs.</p>

        <form class="form-grid" method="POST" action="{{ route('tasks.complete', $task['id']) }}">
            @csrf

            @if ($taskFormError)
                <div class="errors">
                    Could not load task form variables from Operaton: {{ $taskFormError }}
                </div>
            @elseif (! empty($taskFormVariables))
                <div class="grid two">
                    @foreach ($taskFormVariables as $variableName => $metadata)
                        @php
                            $type = strtolower((string) ($metadata['type'] ?? 'string'));
                            $currentValue = old('form_values.' . $variableName, $metadata['value'] ?? null);
                            $enumOptions = data_get($metadata, 'valueInfo.values', []);
                        @endphp

                        <label>
                            {{ $variableName }}

                            @if ($type === 'boolean')
                                <select name="form_values[{{ $variableName }}]">
                                    <option value="" @selected($currentValue === null || $currentValue === '')>No value</option>
                                    <option value="true" @selected($currentValue === true || $currentValue === 'true' || $currentValue === '1' || $currentValue === 1)>True</option>
                                    <option value="false" @selected($currentValue === false || $currentValue === 'false' || $currentValue === '0' || $currentValue === 0)>False</option>
                                </select>
                            @elseif ($type === 'enum' && is_array($enumOptions) && $enumOptions !== [])
                                <select name="form_values[{{ $variableName }}]">
                                    <option value="">Select value</option>
                                    @foreach ($enumOptions as $optionValue)
                                        <option value="{{ $optionValue }}" @selected((string) $currentValue === (string) $optionValue)>
                                            {{ $optionValue }}
                                        </option>
                                    @endforeach
                                </select>
                            @elseif (in_array($type, ['integer', 'long'], true))
                                <input type="number" step="1" name="form_values[{{ $variableName }}]"
                                    value="{{ $currentValue }}">
                            @elseif ($type === 'double')
                                <input type="number" step="any" name="form_values[{{ $variableName }}]"
                                    value="{{ $currentValue }}">
                            @elseif ($type === 'date')
                                <input type="text" name="form_values[{{ $variableName }}]"
                                    value="{{ $currentValue }}" placeholder="2026-07-05T12:00:00">
                            @else
                                <input type="text" name="form_values[{{ $variableName }}]"
                                    value="{{ $currentValue }}">
                            @endif

                            <span class="helper">Type: {{ $metadata['type'] ?? 'String' }}</span>
                        </label>
                    @endforeach
                </div>
            @else
                <div class="bpmn-note">
                    Operaton did not return any explicit form variables for this task. You can still use the advanced JSON box below if the task accepts raw process variables.
                </div>
            @endif

            <label>
                Advanced completion variables JSON
                <textarea name="variables_json" placeholder='{"approved": true, "comment": "Looks good"}'>{{ old('variables_json') }}</textarea>
            </label>

            <p class="helper">
                This box is optional. Use it only for extra scalar variables that are not already represented in the generated form above. Nested arrays or objects are not supported in this stage.
            </p>

            <div class="actions">
                <button type="submit">Complete task</button>
                <a class="button secondary" href="{{ route('tasks.index') }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
