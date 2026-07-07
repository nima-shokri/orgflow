@extends('layouts.app', [
    'title' => 'Process Instance Detail',
    'badge' => 'Stage 10',
    'subtitle' => 'Inspect one Operaton process instance, its variables, and its activity history.',
])

@section('content')
    @php
        $state = strtoupper((string) ($instance['state'] ?? 'UNKNOWN'));
        $stateClass = match ($state) {
            'ACTIVE' => 'published',
            'COMPLETED' => 'archived',
            default => 'draft',
        };
        $durationSeconds = isset($instance['durationInMillis']) ? round(((int) $instance['durationInMillis']) / 1000, 2) : null;
    @endphp

    <section class="panel">
        <div class="nav" style="justify-content: space-between;">
            <div>
                <h2>Process instance</h2>
                <p class="helper">Instance ID: <span class="mono">{{ $instance['id'] }}</span></p>
            </div>

            <div class="nav">
                <span class="status status-{{ $stateClass }}">{{ $state }}</span>
                @if (($instance['suspended'] ?? false) === true)
                    <span class="pill">Suspended</span>
                @endif
            </div>
        </div>

        <div class="meta" style="margin-top: 14px;">
            <span class="pill">Business key: <span class="mono">{{ $instance['businessKey'] ?? 'none' }}</span></span>
            <span class="pill">Started: {{ $instance['startTime'] ?? 'n/a' }}</span>
            <span class="pill">Ended: {{ $instance['endTime'] ?? 'still running' }}</span>
            <span class="pill">Duration: {{ $durationSeconds !== null ? $durationSeconds . 's' : 'n/a' }}</span>
        </div>
    </section>

    <section class="panel">
        <div class="nav">
            <a class="button secondary" href="{{ route('tasks.index', ['instance' => $instance['id']]) }}">Open task inbox</a>
            <a class="button secondary" href="{{ route('runtime.instances.index') }}">Back to runtime explorer</a>

            @if ($linkedDefinition)
                <a class="button secondary" href="{{ route('process-definitions.show', $linkedDefinition) }}">Open linked definition</a>
            @endif

            <a class="button secondary" href="{{ route('operaton.dashboard') }}">Open Operaton dashboard</a>
        </div>
    </section>

    <section class="grid two">
        <div class="panel">
            <h3>Definition details</h3>
            <div class="stack">
                <p class="helper">
                    Definition ID:
                    <span class="mono">{{ $instance['processDefinitionId'] ?? 'n/a' }}</span>
                </p>
                <p class="helper">
                    Process key:
                    <span class="mono">{{ $instance['processDefinitionKey'] ?? ($linkedDefinition?->process_key ?? 'n/a') }}</span>
                </p>
                <p class="helper">
                    Process name:
                    {{ $instance['processDefinitionName'] ?? ($linkedDefinition?->name ?? 'Unknown process') }}
                </p>
                <p class="helper">
                    Process version:
                    {{ isset($instance['processDefinitionVersion']) ? 'v' . $instance['processDefinitionVersion'] : 'n/a' }}
                </p>
            </div>
        </div>

        <div class="panel">
            <h3>History details</h3>
            <div class="stack">
                <p class="helper">Root instance: <span class="mono">{{ $instance['rootProcessInstanceId'] ?? 'n/a' }}</span></p>
                <p class="helper">Start activity: <span class="mono">{{ $instance['startActivityId'] ?? 'n/a' }}</span></p>
                <p class="helper">Ended flag: {{ ($instance['ended'] ?? false) ? 'yes' : 'no' }}</p>
                <p class="helper">Delete reason: {{ $instance['deleteReason'] ?? 'none' }}</p>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="nav" style="justify-content: space-between;">
            <div>
                <h3>Process variables</h3>
                <p class="helper">Historic variable snapshots pulled from Operaton for this process instance.</p>
            </div>

            <span class="pill">{{ count($processVariables) }} variable(s)</span>
        </div>

        @if (! empty($historyWarnings['variables']))
            <div class="errors" style="margin-top: 16px;">
                Could not load process variables from Operaton: {{ $historyWarnings['variables'] }}
            </div>
        @elseif (empty($processVariables))
            <p class="lead">No process variables were returned yet for this instance.</p>
        @else
            <div class="table-wrap" style="margin-top: 18px;">
                <table>
                    <thead>
                        <tr>
                            <th>Variable</th>
                            <th>Value</th>
                            <th>Type</th>
                            <th>Captured at</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($processVariables as $variable)
                            @php
                                $rawValue = $variable['value'] ?? null;
                                $displayValue = match (true) {
                                    is_bool($rawValue) => $rawValue ? 'true' : 'false',
                                    $rawValue === null => 'null',
                                    is_scalar($rawValue) => (string) $rawValue,
                                    default => json_encode($rawValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[unserializable value]',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $variable['name'] ?? 'unnamed' }}</strong><br>
                                    <span class="helper mono">{{ $variable['activityInstanceId'] ?? ($variable['taskId'] ?? 'process scope') }}</span>
                                </td>
                                <td><span class="mono">{{ $displayValue }}</span></td>
                                <td>{{ $variable['type'] ?? 'Unknown' }}</td>
                                <td>{{ $variable['createTime'] ?? ($variable['removalTime'] ?? 'n/a') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="panel">
        <h3>Active tasks</h3>
        @if (empty($activeTasks))
            <p class="lead">No active user task is currently assigned to this process instance.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Task ID</th>
                            <th>Assignee</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activeTasks as $task)
                            <tr>
                                <td>{{ $task['name'] ?? 'Unnamed task' }}</td>
                                <td><span class="mono">{{ $task['id'] ?? 'n/a' }}</span></td>
                                <td>{{ $task['assignee'] ?? 'unassigned' }}</td>
                                <td>{{ $task['created'] ?? 'n/a' }}</td>
                                <td><a href="{{ route('tasks.show', $task['id']) }}">Open task</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="panel">
        <div class="nav" style="justify-content: space-between;">
            <div>
                <h3>Activity timeline</h3>
                <p class="helper">Historic activity instances in ascending start order.</p>
            </div>

            <span class="pill">{{ count($activityHistory) }} activity record(s)</span>
        </div>

        @if (! empty($historyWarnings['activities']))
            <div class="errors" style="margin-top: 16px;">
                Could not load activity history from Operaton: {{ $historyWarnings['activities'] }}
            </div>
        @elseif (empty($activityHistory))
            <p class="lead">No activity history rows were returned yet for this instance.</p>
        @else
            <div class="table-wrap" style="margin-top: 18px;">
                <table>
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>Type</th>
                            <th>Started</th>
                            <th>Ended</th>
                            <th>Duration</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activityHistory as $activity)
                            @php
                                $activityStatus = match (true) {
                                    ($activity['canceled'] ?? false) === true => 'Canceled',
                                    filled($activity['endTime'] ?? null) => 'Completed',
                                    default => 'Running',
                                };
                                $activityStatusClass = match ($activityStatus) {
                                    'Completed' => 'archived',
                                    'Running' => 'published',
                                    default => 'draft',
                                };
                                $durationMillis = $activity['durationInMillis'] ?? null;
                                $durationLabel = is_numeric($durationMillis)
                                    ? round(((float) $durationMillis) / 1000, 2) . 's'
                                    : 'n/a';
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $activity['activityName'] ?? ($activity['activityId'] ?? 'Unnamed activity') }}</strong><br>
                                    <span class="helper mono">{{ $activity['activityId'] ?? 'n/a' }}</span>
                                </td>
                                <td>{{ $activity['activityType'] ?? 'n/a' }}</td>
                                <td>{{ $activity['startTime'] ?? 'n/a' }}</td>
                                <td>{{ $activity['endTime'] ?? 'still running' }}</td>
                                <td>{{ $durationLabel }}</td>
                                <td><span class="status status-{{ $activityStatusClass }}">{{ $activityStatus }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
