@extends('layouts.app', [
    'title' => $mode === 'create' ? 'Create Process Family' : 'Create Process Version',
    'badge' => $mode === 'create' ? 'New Process' : 'Versioning',
    'subtitle' => $mode === 'create'
        ? 'Create a new process family and store its BPMN XML.'
        : 'Create the next BPMN version for an existing process family.',
    'bodyClass' => 'theme-operaton',
    'shellClass' => 'shell-wide',
    'cardClass' => 'card-wide',
    'contentClass' => 'content-wide',
])

@section('content')
    <section class="process-designer-screen">
        <div class="panel process-studio-panel">
            <div class="process-studio-head">
            <h2>{{ $mode === 'create' ? 'Create process family' : 'Create next version' }}</h2>
            <p class="lead">
                @if ($mode === 'create')
                    This screen stores the first BPMN definition for a new process key.
                @else
                    You are creating version <strong>v{{ $nextVersion }}</strong> for
                    <span class="mono">{{ $processKey }}</span>.
                @endif
            </p>
            </div>

            <form class="form-grid process-form-grid" method="POST"
                action="{{ $mode === 'create'
                    ? route('process-definitions.store')
                    : route('process-definitions.versions.store', $baseDefinition) }}">
                @csrf

                <div class="process-meta-grid">
                    @if ($mode === 'create')
                        <label>
                            Process key
                            <input class="mono" type="text" name="process_key" value="{{ $processKey }}" required>
                        </label>
                    @else
                        <div class="process-meta-card">
                            <p class="helper">Process key</p>
                            <strong class="mono">{{ $processKey }}</strong>
                        </div>
                    @endif

                    <label>
                        Process name
                        <input type="text" name="name" value="{{ $name }}" required>
                    </label>

                    <div class="process-meta-card">
                        <p class="helper">Version plan</p>
                        <div class="meta">
                            <span class="pill">Target version: v{{ $nextVersion }}</span>
                            @if ($mode === 'version')
                                <span class="pill">Based on v{{ $baseDefinition->version }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="bpmn-shell" data-bpmn-modeler
                    data-download-name="{{ $processKey }}-v{{ $nextVersion }}.bpmn">
                    <div class="bpmn-toolbar">
                        <button type="button" data-bpmn-action="starter">
                            {{ $mode === 'create' ? 'Reset starter diagram' : 'Load starter diagram' }}
                        </button>
                        <button type="button" data-bpmn-action="import">Import BPMN file</button>
                        <button type="button" data-bpmn-action="download">Download BPMN XML</button>
                        <button type="button" data-bpmn-action="fit">Fit diagram</button>
                    </div>

                    <div class="bpmn-status" data-bpmn-status>
                        Loading the BPMN modeler. If the editor does not appear, run <code>npm run build</code> or
                        <code>npm run dev</code>.
                    </div>

                    <div class="bpmn-workspace">
                        <div class="bpmn-canvas" data-bpmn-canvas></div>

                        <div class="bpmn-sidebar">
                            <div class="bpmn-note">
                                Use the visual editor for normal work. The XML field remains available as a fallback and
                                is automatically synchronized before save.
                            </div>

                            <div class="bpmn-xml-panel">
                                <label>
                                    BPMN XML fallback
                                    <textarea name="bpmn_xml" data-bpmn-xml required>{{ $bpmnXml }}</textarea>
                                </label>

                                <p class="helper">
                                    Validation checks require a BPMN <code>definitions</code> root node and at least one
                                    <code>process</code> node.
                                </p>
                            </div>
                        </div>
                    </div>

                    <input type="file" data-bpmn-file accept=".bpmn,.xml" hidden>
                </div>

                <div class="actions">
                    <button type="submit" name="intent" value="draft">Save draft</button>
                    <button type="submit" name="intent" value="publish">Publish version</button>
                    <a class="button secondary" href="{{ route('process-definitions.index') }}">Back to library</a>
                </div>
            </form>
        </div>

        <section class="process-guide-grid">
            <div class="panel">
                <h3>Process key rules</h3>
                <p>Use lowercase letters, numbers, dashes, underscores, or dots. Example: <code>invoice-approval</code></p>
            </div>
            <div class="panel">
                <h3>Designer workflow</h3>
                <p>Use <code>Reset starter diagram</code> after changing the process key on a brand-new family so the initial diagram IDs match your chosen key.</p>
            </div>
            <div class="panel">
                <h3>Publishing behavior</h3>
                <p>Publishing a new version automatically archives the previously published version of the same process key.</p>
            </div>
            <div class="panel">
                <h3>Stage outcome</h3>
                <p>We can now persist BPMN definitions independently from runtime execution and attach explicit versions to every process family.</p>
            </div>
            <div class="panel">
                <h3>Editing fallback</h3>
                <p>The raw XML field stays available as a fallback, but the designer now gets the full width of the page first.</p>
            </div>
            <div class="panel">
                <h3>Run checkpoint</h3>
                <p>After any frontend change, rebuild assets once and then hard refresh the browser so the newest modeler bundle loads.</p>
            </div>
        </section>
    </section>
@endsection
