@extends('layouts.app', [
    'title' => 'Process Runtime',
    'badge' => 'Stage 10',
    'subtitle' => 'Start deployed process versions and inspect runtime instances, variables, and activity history.',
])

@section('content')
    <section class="stats">
        <div class="stat">
            <strong>{{ $status['ok'] ? 'UP' : 'DOWN' }}</strong>
            <p class="helper">Engine connectivity</p>
        </div>
        <div class="stat">
            <strong>{{ $deployedDefinitions->count() }}</strong>
            <p class="helper">Definitions linked to runtime</p>
        </div>
        <div class="stat">
            <strong>{{ count($instances) }}</strong>
            <p class="helper">Recent instances loaded</p>
        </div>
    </section>

    <section class="panel">
        <div class="nav" style="justify-content: space-between;">
            <div>
                <h2>Runtime explorer</h2>
                <p class="lead">Use this page to start published deployments quickly and to inspect process instances reported by Operaton.</p>
            </div>

            <div class="nav">
                <a class="button secondary" href="{{ route('tasks.index') }}">Open task inbox</a>
                <a class="button secondary" href="{{ route('operaton.dashboard') }}">Open Operaton dashboard</a>
                <a class="button secondary" href="{{ route('admin.dashboard') }}">Back to admin</a>
            </div>
        </div>

        <div class="meta" style="margin-top: 14px;">
            <span class="pill">REST base URL: <span class="mono">{{ $status['base_url'] }}</span></span>
            <span class="pill">Web app: <span class="mono">{{ $status['web_url'] }}</span></span>
            <span class="pill">Status: {{ $status['message'] }}</span>
        </div>
    </section>

    <section class="panel">
        <div class="nav" style="justify-content: space-between;">
            <div>
                <h3>Startable definitions</h3>
                <p class="helper">These versions are already linked to Operaton from Laravel and can be inspected or started.</p>
            </div>

            @if ($selectedDefinition)
                <a class="button secondary" href="{{ route('runtime.instances.index') }}">Clear filter</a>
            @endif
        </div>

        @if ($deployedDefinitions->isEmpty())
            <p class="lead">Deploy at least one process definition first, then come back here to start runtime instances.</p>
        @else
            <div class="table-wrap" style="margin-top: 18px;">
                <table>
                    <thead>
                        <tr>
                            <th>Process</th>
                            <th>Version</th>
                            <th>Local status</th>
                            <th>Engine definition</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($deployedDefinitions as $definition)
                            <tr>
                                <td>
                                    <strong>{{ $definition->name }}</strong><br>
                                    <span class="helper mono">{{ $definition->process_key }}</span>
                                </td>
                                <td>v{{ $definition->version }}</td>
                                <td><span class="status status-{{ $definition->status }}">{{ $definition->status }}</span></td>
                                <td><span class="mono">{{ $definition->engine_process_definition_id }}</span></td>
                                <td>
                                    <div class="nav">
                                        <a href="{{ route('process-definitions.show', $definition) }}">Open</a>
                                        <a href="{{ route('process-definitions.show', $definition) }}#runtime-execution">Start form</a>
                                        <a href="{{ route('runtime.instances.index', ['definition' => $definition->engine_process_definition_id]) }}">Instances</a>
                                        <a href="{{ route('tasks.index', ['definition' => $definition->engine_process_definition_id]) }}">Tasks</a>

                                        @if ($definition->isPublished())
                                            <form class="inline-form" method="POST"
                                                action="{{ route('process-definitions.start', $definition) }}">
                                                @csrf
                                                <button type="submit">Quick start</button>
                                            </form>
                                        @else
                                            <span class="pill">Archived version</span>
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

    <section class="panel">
        <div class="nav" style="justify-content: space-between;">
            <div>
                <h3>Recent process instances</h3>
                <p class="helper">
                    @if ($selectedDefinition)
                        Filtered by <span class="mono">{{ $selectedDefinition->process_key }}</span> v{{ $selectedDefinition->version }}.
                    @else
                        Showing the latest process instances from Operaton history.
                    @endif
                </p>
            </div>
        </div>

        @if (! $status['ok'])
            <p class="lead">Laravel cannot query Operaton yet. Start the runtime containers first, then refresh this page.</p>
        @elseif (empty($instances))
            <p class="lead">No process instances were returned yet. Start one from a deployed definition and then refresh.</p>
        @else
            <div class="table-wrap" style="margin-top: 18px;">
                <table>
                    <thead>
                        <tr>
                            <th>Instance</th>
                            <th>Process</th>
                            <th>Business key</th>
                            <th>Started</th>
                            <th>State</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($instances as $instance)
                            @php
                                $state = strtoupper((string) ($instance['state'] ?? 'UNKNOWN'));
                                $stateClass = match ($state) {
                                    'ACTIVE' => 'published',
                                    'COMPLETED' => 'archived',
                                    default => 'draft',
                                };
                            @endphp
                            <tr>
                                <td><span class="mono">{{ $instance['id'] ?? 'n/a' }}</span></td>
                                <td>
                                    <strong>{{ $instance['processDefinitionName'] ?? 'Unnamed process' }}</strong><br>
                                    <span class="helper mono">{{ $instance['processDefinitionKey'] ?? ($selectedDefinition->process_key ?? 'n/a') }}</span>
                                </td>
                                <td><span class="mono">{{ $instance['businessKey'] ?? 'none' }}</span></td>
                                <td>{{ $instance['startTime'] ?? 'n/a' }}</td>
                                <td><span class="status status-{{ $stateClass }}">{{ $state }}</span></td>
                                <td>
                                    <div class="nav">
                                        <a href="{{ route('runtime.instances.show', $instance['id']) }}">Open instance</a>
                                        <a href="{{ route('tasks.index', ['instance' => $instance['id']]) }}">Tasks</a>
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
