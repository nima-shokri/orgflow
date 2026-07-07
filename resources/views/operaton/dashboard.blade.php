@extends('layouts.app', [
    'title' => 'Operaton Engine',
    'badge' => 'Stage 05',
    'subtitle' => 'Check engine connectivity and deploy published BPMN versions into runtime.',
])

@section('content')
    <section class="panel">
        <div class="nav" style="justify-content: space-between;">
            <div>
                <h2>Operaton engine</h2>
                <p class="lead">This page proves Laravel can reach the runtime engine and inspect deployed process definitions.</p>
            </div>

            <div class="nav">
                <a class="button secondary" href="{{ route('tasks.index') }}">Open task inbox</a>
                <a class="button secondary" href="{{ route('runtime.instances.index') }}">Open runtime explorer</a>
                <a class="button secondary" href="{{ route('process-definitions.index') }}">Back to library</a>
                <a class="button secondary" href="{{ route('admin.dashboard') }}">Back to admin</a>
            </div>
        </div>
    </section>

    <section class="stats">
        <div class="stat">
            <strong>{{ $status['ok'] ? 'UP' : 'DOWN' }}</strong>
            <p class="helper">Engine connectivity</p>
        </div>
        <div class="stat">
            <strong>{{ $status['version'] ?? 'n/a' }}</strong>
            <p class="helper">Reported Operaton version</p>
        </div>
        <div class="stat">
            <strong>{{ count($engineDefinitions) }}</strong>
            <p class="helper">Latest runtime definitions</p>
        </div>
    </section>

    <section class="panel">
        <h3>Connection details</h3>
        <div class="stack">
            <p class="helper">REST base URL: <span class="mono">{{ $status['base_url'] }}</span></p>
            <p class="helper">Web app URL: <span class="mono">{{ $status['web_url'] }}</span></p>
            <p class="helper">Status message: {{ $status['message'] }}</p>
        </div>
    </section>

    <section class="panel">
        <h3>Published definitions ready for deployment</h3>
        @if ($publishedDefinitions->isEmpty())
            <p class="lead">Publish at least one process version first, then come back here to deploy it to the runtime engine.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Process</th>
                            <th>Version</th>
                            <th>Owner</th>
                            <th>Engine status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($publishedDefinitions as $definition)
                            <tr>
                                <td>
                                    <strong>{{ $definition->name }}</strong><br>
                                    <span class="helper mono">{{ $definition->process_key }}</span>
                                </td>
                                <td>v{{ $definition->version }}</td>
                                <td>{{ $definition->creator?->name ?? 'system' }}</td>
                                <td>
                                    @if ($definition->isDeployed())
                                        <span class="status status-published">Deployed</span>
                                    @else
                                        <span class="status status-draft">Not deployed</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="nav">
                                        <a href="{{ route('process-definitions.show', $definition) }}">Open</a>
                                        <a href="{{ route('tasks.index', ['definition' => $definition->engine_process_definition_id]) }}">Tasks</a>
                                        <form class="inline-form" method="POST"
                                            action="{{ route('process-definitions.deploy', $definition) }}">
                                            @csrf
                                            <button type="submit">Deploy</button>
                                        </form>
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
        <h3>Runtime definitions reported by Operaton</h3>
        @if (! $status['ok'])
            <p class="lead">Laravel could not query the engine yet. Start the Operaton containers first, then refresh this page.</p>
        @elseif (empty($engineDefinitions))
            <p class="lead">The engine is reachable, but no process definitions are deployed yet.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Name</th>
                            <th>Version</th>
                            <th>Deployment ID</th>
                            <th>Definition ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($engineDefinitions as $definition)
                            <tr>
                                <td><span class="mono">{{ $definition['key'] ?? 'n/a' }}</span></td>
                                <td>{{ $definition['name'] ?? 'Unnamed process' }}</td>
                                <td>v{{ $definition['version'] ?? '?' }}</td>
                                <td><span class="mono">{{ $definition['deploymentId'] ?? 'n/a' }}</span></td>
                                <td><span class="mono">{{ $definition['id'] ?? 'n/a' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
