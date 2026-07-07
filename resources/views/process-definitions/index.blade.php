@extends('layouts.app', [
    'title' => 'Process Definitions',
    'badge' => 'Process Library',
    'subtitle' => 'Stage 03: BPMN XML storage, versioning, and publishing.',
    'bodyClass' => 'theme-operaton',
    'shellClass' => 'shell-wide',
    'contentClass' => 'content-wide',
])

@section('content')
    <section class="stats">
        <div class="stat">
            <strong>{{ $familyCount }}</strong>
            <p class="helper">Process families</p>
        </div>
        <div class="stat">
            <strong>{{ $versionCount }}</strong>
            <p class="helper">Stored versions</p>
        </div>
        <div class="stat">
            <strong>{{ $publishedCount }}</strong>
            <p class="helper">Published versions</p>
        </div>
    </section>

    <section class="panel">
        <div class="nav">
            <a class="button" href="{{ route('process-definitions.create') }}">Create process family</a>
            <a class="button secondary" href="{{ route('admin.dashboard') }}">Back to admin</a>
        </div>
    </section>

    @if ($families->isEmpty())
        <section class="panel">
            <h2>No process definitions yet</h2>
            <p class="lead">Create the first BPMN process family to start building the process library.</p>
        </section>
    @else
        <section class="stack">
            @foreach ($families as $processKey => $versions)
                @php($latest = $versions->first())
                <article class="panel">
                    <div class="nav" style="justify-content: space-between;">
                        <div>
                            <h2>{{ $latest->name }}</h2>
                            <p class="helper">Process key: <span class="mono">{{ $processKey }}</span></p>
                        </div>

                        <div class="nav">
                            <span class="status status-{{ $latest->status }}">{{ $latest->status }}</span>
                            <a class="button secondary" href="{{ route('process-definitions.show', $latest) }}">Open latest version</a>
                        </div>
                    </div>

                    <div class="meta">
                        <span class="pill">Latest version: v{{ $latest->version }}</span>
                        <span class="pill">Family size: {{ $versions->count() }} version(s)</span>
                        <span class="pill">Owner: {{ $latest->creator?->name ?? 'system' }}</span>
                    </div>

                    <div class="table-wrap" style="margin-top: 18px;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Version</th>
                                    <th>Status</th>
                                    <th>Updated</th>
                                    <th>Owner</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($versions as $definition)
                                    <tr>
                                        <td><strong>v{{ $definition->version }}</strong></td>
                                        <td><span class="status status-{{ $definition->status }}">{{ $definition->status }}</span></td>
                                        <td>{{ $definition->updated_at->diffForHumans() }}</td>
                                        <td>{{ $definition->creator?->name ?? 'system' }}</td>
                                        <td>
                                            <a href="{{ route('process-definitions.show', $definition) }}">View details</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </article>
            @endforeach
        </section>
    @endif
@endsection
