@extends('layouts.app', [
    'title' => 'Admin Dashboard',
    'badge' => 'Admin',
    'subtitle' => 'Central control area for process design, deployment, and runtime management.',
])

@section('content')
    <section class="panel">
        <h2>Admin dashboard</h2>
        <p class="lead">This page is protected by the <code>role:admin</code> middleware.</p>

        <div class="meta">
            <span class="pill">Role: {{ auth()->user()->role }}</span>
            <span class="pill">Access: administrator only</span>
        </div>
    </section>

    <section class="panel">
        <h3>Why this matters</h3>
        <p>This is the first layer of RBAC for the BPMS. In later stages we can expand this into permissions for process design, deployment, execution, and task handling.</p>

        <div class="nav">
            <a class="button" href="{{ route('process-definitions.index') }}">Open process library</a>
            <a class="button secondary" href="{{ route('operaton.dashboard') }}">Open Operaton dashboard</a>
            <a class="button secondary" href="{{ route('runtime.instances.index') }}">Open runtime explorer</a>
            <a class="button secondary" href="{{ route('tasks.index') }}">Open task inbox</a>
            <a class="button secondary" href="{{ route('dashboard') }}">Back to user dashboard</a>

            <form class="inline-form" method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Logout</button>
            </form>
        </div>
    </section>
@endsection
