@extends('layouts.app', [
    'title' => 'Process Definition Details',
    'badge' => 'Definition Detail',
    'subtitle' => 'Inspect BPMN XML, publishing state, and version history.',
    'bodyClass' => 'theme-operaton',
    'shellClass' => 'shell-wide',
    'contentClass' => 'content-wide',
])

@section('content')
    <section class="panel">
        <div class="nav" style="justify-content: space-between;">
            <div>
                <h2>{{ $definition->name }}</h2>
                <p class="helper">Process key: <span class="mono">{{ $definition->process_key }}</span></p>
            </div>

            <div class="nav">
                <span class="status status-{{ $definition->status }}">{{ $definition->status }}</span>
                <span class="pill">Version v{{ $definition->version }}</span>
            </div>
        </div>

        <div class="meta">
            <span class="pill">Created by: {{ $definition->creator?->name ?? 'system' }}</span>
            <span class="pill">Updated: {{ $definition->updated_at->diffForHumans() }}</span>
            @if ($definition->published_at)
                <span class="pill">Published: {{ $definition->published_at->diffForHumans() }}</span>
            @endif
        </div>
    </section>

    <section class="panel">
        <div class="nav">
            <a class="button" href="{{ route('process-definitions.versions.create', $definition) }}">Create next version</a>

            @if (! $definition->isPublished())
                <form class="inline-form" method="POST" action="{{ route('process-definitions.publish', $definition) }}">
                    @csrf
                    <button type="submit">Publish this version</button>
                </form>
            @endif

            <a class="button secondary" href="{{ route('process-definitions.index') }}">Back to library</a>
        </div>
    </section>

    <section class="panel">
        <div class="nav" style="justify-content: space-between;">
            <div>
                <h3>Runtime deployment</h3>
                <p class="helper">Deploy the published BPMN version into Operaton and keep the runtime identifiers attached to this definition.</p>
            </div>

            <div class="nav">
                <a class="button secondary" href="{{ route('operaton.dashboard') }}">Open Operaton dashboard</a>
                @if ($definition->isPublished())
                    <form class="inline-form" method="POST" action="{{ route('process-definitions.deploy', $definition) }}">
                        @csrf
                        <button type="submit">Deploy to Operaton</button>
                    </form>
                @else
                    <span class="pill">Publish before deploy</span>
                @endif
            </div>
        </div>

        <div class="meta" style="margin-top: 14px;">
            <span class="pill">Deployment ID: <span class="mono">{{ $definition->engine_deployment_id ?? 'not deployed yet' }}</span></span>
            <span class="pill">Definition ID: <span class="mono">{{ $definition->engine_process_definition_id ?? 'not available yet' }}</span></span>
            <span class="pill">
                Deployed at:
                {{ $definition->engine_deployed_at?->diffForHumans() ?? 'not deployed yet' }}
            </span>
        </div>

        @if ($definition->engine_deployment_error)
            <div class="errors" style="margin-top: 18px;">
                Last deployment error: {{ $definition->engine_deployment_error }}
            </div>
        @endif
    </section>

    <section class="panel" id="runtime-execution">
        <div class="nav" style="justify-content: space-between;">
            <div>
                <h3>Runtime execution</h3>
                <p class="helper">Start a process instance against the deployed version, optionally inject scalar start variables, and inspect the result in the runtime explorer.</p>
            </div>

            <div class="nav">
                <a class="button secondary"
                    href="{{ route('runtime.instances.index', ['definition' => $definition->engine_process_definition_id]) }}">
                    Open runtime explorer
                </a>
            </div>
        </div>

        @if ($definition->isPublished() && $definition->isDeployed())
            <form class="form-grid" method="POST" action="{{ route('process-definitions.start', $definition) }}"
                style="margin-top: 18px;">
                @csrf

                <div class="grid two">
                    <label>
                        Optional business key
                        <input type="text" name="business_key" maxlength="120" value="{{ old('business_key') }}"
                            placeholder="{{ $definition->process_key }}-demo-001">
                    </label>

                    <div class="actions" style="align-self: end;">
                        <button type="submit">Start process instance</button>
                    </div>
                </div>

                <label>
                    Optional start variables JSON
                    <textarea name="variables_json" rows="6" style="min-height: 140px;"
                        placeholder='{"approved": false, "amount": 1250, "requester": "nima"}'>{{ old('variables_json') }}</textarea>
                </label>

                <p class="helper">
                    If you leave the business key empty, the engine will still start the process, but the instance will not have a custom external identifier.
                    Start variables must be a JSON object and currently support only scalar values or null.
                    After start, you can inspect them on the runtime detail page.
                </p>
            </form>
        @elseif (! $definition->isPublished())
            <p class="lead" style="margin-top: 18px;">Only the currently published process version can be started from Laravel in this stage.</p>
        @else
            <p class="lead" style="margin-top: 18px;">Deploy this version to Operaton first, then you can start instances from here.</p>
        @endif
    </section>

    <section class="panel bpmn-preview" data-bpmn-viewer
        data-download-name="{{ $definition->process_key }}-v{{ $definition->version }}.bpmn">
        <div class="bpmn-toolbar">
            <button type="button" data-bpmn-action="download">Download BPMN XML</button>
            <button type="button" data-bpmn-action="fit">Fit preview</button>
        </div>

        <div class="bpmn-status" data-bpmn-status>
            Loading BPMN preview. If the canvas stays empty, run <code>npm run build</code> or <code>npm run dev</code>.
        </div>

        <div class="bpmn-canvas" data-bpmn-canvas></div>
        <textarea data-bpmn-xml hidden>{{ $definition->bpmn_xml }}</textarea>
    </section>

    <section class="grid two">
        <div class="panel">
            <h3>XML details</h3>
            <div class="stack">
                <p class="helper">Root node: <span class="mono">{{ $xmlDetails['root'] }}</span></p>
                <p class="helper">Process IDs found:</p>
                <div class="meta">
                    @forelse ($xmlDetails['process_ids'] as $processId)
                        <span class="pill mono">{{ $processId }}</span>
                    @empty
                        <span class="pill">No process ID found</span>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="panel">
            <h3>Version history</h3>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Version</th>
                            <th>Status</th>
                            <th>Owner</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($versions as $version)
                            <tr>
                                <td><strong>v{{ $version->version }}</strong></td>
                                <td><span class="status status-{{ $version->status }}">{{ $version->status }}</span></td>
                                <td>{{ $version->creator?->name ?? 'system' }}</td>
                                <td><a href="{{ route('process-definitions.show', $version) }}">Open</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="panel">
        <h3>BPMN XML</h3>
        <pre class="code-block">{{ $definition->bpmn_xml }}</pre>
    </section>
@endsection
