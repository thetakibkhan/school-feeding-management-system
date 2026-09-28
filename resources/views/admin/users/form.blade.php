@if ($errors->any())
    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
@endif
<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method !== 'POST') @method($method) @endif
    <p><label for="name">Name</label><br><input id="name" name="name" value="{{ old('name', $user?->name) }}" required></p>
    <p><label for="email">Email</label><br><input id="email" name="email" type="email" value="{{ old('email', $user?->email) }}" required></p>
    @if ($passwordRequired)
        <p><label for="password">Password</label><br><span class="password-toggle-field"><input id="password" name="password" type="password" required><button class="password-toggle-button" type="button" data-password-toggle aria-controls="password" aria-label="Show password" aria-pressed="false">Show</button></span></p>
        <p><label for="password_confirmation">Confirm password</label><br><span class="password-toggle-field"><input id="password_confirmation" name="password_confirmation" type="password" required><button class="password-toggle-button" type="button" data-password-toggle aria-controls="password_confirmation" aria-label="Show password" aria-pressed="false">Show</button></span></p>
    @endif
    <p><label for="role">Role</label><br><select id="role" name="role" required>
        @foreach ($roles ?? \App\Enums\UserRole::cases() as $role)
            <option value="{{ $role->value }}" @selected(old('role', $user?->role?->value) === $role->value)>{{ $role->value }}</option>
        @endforeach
    </select></p>
    <button type="submit">Save user</button>
</form>
