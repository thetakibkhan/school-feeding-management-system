<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in · Prottyashi SFP</title>
    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])
</head>
<body class="clean-signin-page">
    <x-auth-shell>
        <x-slot:background>
            <x-beams-background />
        </x-slot:background>
    <main class="clean-signin-card">
        <div class="clean-signin-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M15 8V6.5A3.5 3.5 0 0 0 8 6.5v11A3.5 3.5 0 0 0 15 17V15" /><path d="M12 12h9m0 0-3-3m3 3-3 3" /></svg>
        </div>
        <h1>Sign in with email</h1>
        <p class="clean-signin-description">Manage school feeding operations, deliveries, and reports in one place.</p>
        @if ($errors->any())
            <div class="clean-signin-error" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        <form class="clean-signin-form" method="POST" action="{{ route('login') }}">
            @csrf
            <div class="clean-signin-input-wrap">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5h16v14H4z" /><path d="m4 7 8 6 8-6" /></svg>
                <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="Email" autocomplete="email" required autofocus>
            </div>
            <div class="clean-signin-input-wrap">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="10" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3" /></svg>
                <input id="password" name="password" type="password" placeholder="Password" autocomplete="current-password" required>
            </div>
            <div class="clean-signin-options">
                <label class="clean-signin-remember" for="remember"><input id="remember" name="remember" type="checkbox" value="1"> Remember me</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}">Forgot password?</a>
                @endif
            </div>
            <button class="clean-signin-submit" type="submit">Get Started</button>
        </form>
        <div class="clean-signin-divider"><span>Or sign in with</span></div>
        <div class="clean-signin-socials" aria-label="Social sign-in providers">
            <button type="button" disabled title="Social sign-in is not enabled">G</button>
            <button type="button" disabled title="Social sign-in is not enabled">f</button>
            <button type="button" disabled title="Social sign-in is not enabled">●</button>
        </div>
        <p class="clean-signin-note">Social sign-in is not enabled for this assessment build.</p>
    </main>
    </x-auth-shell>
</body>
</html>
