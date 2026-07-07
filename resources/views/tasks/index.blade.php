@extends('layouts.app', [
    'title' => 'Task Inbox',
    'badge' => 'Stage 09',
    'subtitle' => 'Claim, release, inspect, and complete active Operaton user tasks from the Laravel inbox.',
])

@section('content')
    <section class="stats">
        <div class="stat">
            <strong>{{ $status['ok'] ? 'UP' : 'DOWN' }}</strong>
            <p class="helper">Engine connectivity</p>
        </div>
        <div class="stat">
            <strong>{{ count($tasks) }}</strong>
            <p class="helper">Visible tasks in current scope</p>
        </div>
        <div class="stat">
            <strong>{{ $availableTaskCount }}</strong>
            <p class="helper">Active tasks before scope filter</p>
        </div>
    </section>

    <section class="panel">
        <div class="nav" style="justify-content: space-between;">
            <div>
                <h2>Task inbox</h2>
                <p class="lead">This stage adds the first task-handling flow in Laravel: list active tasks, inspect one task, and complete it.</p>
            </div>

            <div class="nav">
                <a class="button secondary" href="{{ route('runtime.instances.index') }}">Open runtime explorer</a>
                <a class="button secondary" href="{{ route('admin.dashboard') }}">Back to admin</a>
            </div>
        </div>

        <div class="meta" style="margin-top: 14px;">
            <span class="pill">REST base URL: <span class="mono">{{ $status['base_url'] }}</span></span>
            <span class="pill">Status: {{ $status['message'] }}</span>
            <span class="pill">Your assignee ID: <span class="mono">{{ $currentAssignee }}</span></span>
            @if ($selectedInstanceId !== '')
                <span class="pill">Instance filter: <span class="mono">{{ $selectedInstanceId }}</span></span>
            @endif
            @if ($selectedDefinitionId !== '')
                <span class="pill">Definition filter: <span class="mono">{{ $selectedDefinitionId }}</span></span>
            @endif
        </div>
    </section>

    <section class="panel">
        <div class="nav" style="justify-content: space-between;">
            <div>
                <h3>Active tasks</h3>
                <p class="helper">This inbox now supports a lightweight ownership flow: claim a task for yourself, release it, and filter the list by scope.</p>
            </div>

            <div class="nav">
                <a class="button {{ $scope === 'all' ? '' : 'secondary' }}"
                    href="{{ route('tasks.index', array_filter(['instance' => $selectedInstanceId, 'definition' => $selectedDefinitionId])) }}">
                    All
                </a>
                <a class="button {{ $scope === 'mine' ? '' : 'secondary' }}"
                    href="{{ route('tasks.index', array_filter(['instance' => $selectedInstanceId, 'definition' => $selectedDefinitionId, 'scope' => 'mine'])) }}">
                    Mine
                </a>
                <a class="button {{ $scope === 'unassigned' ? '' : 'secondary' }}"
                    href="{{ route('tasks.index', array_filter(['instance' => $selectedInstanceId, 'definition' => $selectedDefinitionId, 'scope' => 'unassigned'])) }}">
                    Unassigned
                </a>

                @if ($selectedInstanceId !== '' || $selectedDefinitionId !== '' || $scope !== 'all')
                    <a class="button secondary" href="{{ route('tasks.index') }}">Clear filters</a>
                @endif
            </div>
        </div>

        @if (! $status['ok'])
            <p class="lead">Laravel cannot query Operaton right now. Start the runtime containers first, then refresh this page.</p>
        @elseif (empty($tasks))
            <p class="lead">No active tasks were returned. Start a process that pauses on a user task, then refresh the inbox.</p>
        @else
            <div class="table-wrap" style="margin-top: 18px;">
                <table>
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Process</th>
                            <th>Instance</th>
                            <th>Assignee</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tasks as $task)
                            @php($linkedDefinition = $linkedDefinitions->get($task['processDefinitionId'] ?? ''))
                            <tr>
                                <td>
                                    <strong>{{ $task['name'] ?? 'Unnamed task' }}</strong><br>
                                    <span class="helper mono">{{ $task['taskDefinitionKey'] ?? ($task['id'] ?? 'n/a') }}</span>
                                </td>
                                <td>
                                    @if ($linkedDefinition)
                                        <strong>{{ $linkedDefinition->name }}</strong><br>
                                        <span class="helper mono">{{ $linkedDefinition->process_key }}</span>
                                    @else
                                        <span class="mono">{{ $task['processDefinitionId'] ?? 'n/a' }}</span>
                                    @endif
                                </td>
                                <td><span class="mono">{{ $task['processInstanceId'] ?? 'n/a' }}</span></td>
                                <td>
                                    @if (($task['assignee'] ?? null) === $currentAssignee)
                                        <strong>{{ $task['assignee'] }}</strong><br>
                                        <span class="helper">Assigned to you</span>
                                    @else
                                        {{ $task['assignee'] ?? 'unassigned' }}
                                    @endif
                                </td>
                                <td>{{ $task['created'] ?? 'n/a' }}</td>
                                <td>
                                    <div class="nav">
                                        <a href="{{ route('tasks.show', $task['id']) }}">Open task</a>
                                        @if (! empty($task['processInstanceId']))
                                            <a href="{{ route('runtime.instances.show', $task['processInstanceId']) }}">Open instance</a>
                                        @endif
                                        @if (blank($task['assignee'] ?? null))
                                            <form class="inline-form" method="POST" action="{{ route('tasks.claim', $task['id']) }}">
                                                @csrf
                                                <button type="submit">Claim</button>
                                            </form>
                                        @elseif (($task['assignee'] ?? null) === $currentAssignee)
                                            <form class="inline-form" method="POST" action="{{ route('tasks.release', $task['id']) }}">
                                                @csrf
                                                <button type="submit">Release</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
