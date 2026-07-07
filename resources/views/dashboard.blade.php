@extends('layouts.app', [
    'title' => 'User Dashboard',
    'badge' => 'Workspace',
    'subtitle' => 'Signed-in users land here before moving into process, runtime, or admin flows.',
])

@section('content')
    <section class="panel">
        <h2>User dashboard</h2>
        <p class="lead">You are signed in and the base authorization layer is active.</p>

        <div class="meta">
            <span class="pill">User: {{ auth()->user()->name }}</span>
            <span class="pill">Email: {{ auth()->user()->email }}</span>
            <span class="pill">Role: {{ auth()->user()->role }}</span>
        </div>
    </section>

    <section class="panel">
        <h3>Available actions</h3>
        <div class="nav">
            @if (auth()->user()->isAdmin())
                <a class="button" href="{{ route('admin.dashboard') }}">Open admin area</a>
            @endif

            <form class="inline-form" method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Logout</button>
            </form>
        </div>
    </section>
@endsection
