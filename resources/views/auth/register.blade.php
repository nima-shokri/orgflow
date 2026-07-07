@extends('layouts.app', [
    'title' => 'Register Operator',
    'badge' => 'Users',
    'subtitle' => 'Create a new operator account inside the Laravel BPMS workspace.',
])

@section('content')
    <div class="grid two">
        <section class="panel">
            <h2>Register operator</h2>
            <p class="lead">New registrations are created with the <code>operator</code> role by default.</p>

            <form class="form-grid" method="POST" action="{{ route('register.store') }}">
                @csrf

                <label>
                    Full name
                    <input type="text" name="name" value="{{ old('name') }}" required autofocus>
                </label>

                <label>
                    Email
                    <input type="email" name="email" value="{{ old('email') }}" required>
                </label>

                <label>
                    Password
                    <input type="password" name="password" required>
                </label>

                <label>
                    Confirm password
                    <input type="password" name="password_confirmation" required>
                </label>

                <div class="actions">
                    <button type="submit">Create account</button>
                    <a class="button secondary" href="{{ route('login') }}">Back to login</a>
                </div>
            </form>
        </section>

        <aside class="panel">
            <h3>What this stage proves</h3>
            <p>We are validating that session auth works, users can sign in, and the system can separate normal users from admins.</p>
            <p>The next stage can build process features on top of this access layer.</p>
        </aside>
    </div>
@endsection
