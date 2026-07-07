@extends('layouts.app', [
    'title' => 'Sign In',
    'badge' => 'Access',
    'subtitle' => 'Authenticate into the BPMS workspace and continue to the Laravel control surface.',
])

@section('content')
    <div class="grid two">
        <section class="panel">
            <h2>Sign in</h2>
            <p class="lead">Use one of the seeded users, or register a new operator account.</p>

            <form class="form-grid" method="POST" action="{{ route('login.attempt') }}">
                @csrf

                <label>
                    Email
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus>
                </label>

                <label>
                    Password
                    <input type="password" name="password" required>
                </label>

                <label>
                    <input type="checkbox" name="remember" value="1">
                    Remember me
                </label>

                <div class="actions">
                    <button type="submit">Login</button>
                    <a class="button secondary" href="{{ route('register') }}">Create operator account</a>
                </div>
            </form>
        </section>

        <aside class="panel">
            <h3>Seeded users</h3>
            <p>Use these accounts to test role-based access right away.</p>
            <div class="stack">
                <div>
                    <strong>Admin</strong>
                    <p>Email: <code>admin@bpms.test</code><br>Password: <code>password</code></p>
                </div>
                <div>
                    <strong>Operator</strong>
                    <p>Email: <code>operator@bpms.test</code><br>Password: <code>password</code></p>
                </div>
            </div>
        </aside>
    </div>
@endsection
