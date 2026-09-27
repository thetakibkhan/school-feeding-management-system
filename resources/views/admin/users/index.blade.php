<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User management</title>
    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])
</head>
<body>
    <x-dashboard-shell active="Users">
        <section class="user-management" data-editable-table>
            <div class="user-management__heading">
                <div><p class="dashboard-eyebrow">Administration</p><h2>Users</h2><p>Manage the people who can access the feeding system.</p></div>
                <button class="user-management__primary-action" type="button" data-modal-open="create-user-modal">+ Create user</button>
            </div>
            @if (session('status')) <div class="user-management__notice" role="status">{{ session('status') }}</div> @endif
            <div class="editable-table-card">
                <div class="editable-table-toolbar">
                    <label class="editable-table-search"><span aria-hidden="true">⌕</span><input type="search" placeholder="Search users" data-table-search></label>
                    <span class="editable-table-count"><strong data-table-visible-count>{{ $users->count() }}</strong> users</span>
                </div>
                <div class="editable-table-scroll">
                    <table class="editable-table">
                        <thead><tr><th class="editable-table__check"><input type="checkbox" data-table-select-all aria-label="Select all users"></th><th><button type="button" data-table-sort="name">Name <span>↕</span></button></th><th><button type="button" data-table-sort="email">Email <span>↕</span></button></th><th><button type="button" data-table-sort="role">Role <span>↕</span></button></th><th><button type="button" data-table-sort="status">Status <span>↕</span></button></th><th>Actions</th></tr></thead>
                        <tbody data-table-body>
                            @foreach ($users as $user)
                                <tr data-table-row data-name="{{ strtolower($user->name) }}" data-email="{{ strtolower($user->email) }}" data-role="{{ $user->role->value }}" data-status="{{ $user->isActive() ? 'active' : 'inactive' }}">
                                    <td class="editable-table__check"><input type="checkbox" data-table-row-select aria-label="Select {{ $user->name }}"></td>
                                    <td><div class="user-cell"><span class="user-cell__avatar">{{ strtoupper(substr($user->name, 0, 2)) }}</span><strong>{{ $user->name }}</strong></div></td>
                                    <td class="editable-table__muted">{{ $user->email }}</td><td><span class="user-role">{{ str_replace('_', ' ', $user->role->value) }}</span></td>
                                    <td><span class="user-status user-status--{{ $user->isActive() ? 'active' : 'inactive' }}">{{ $user->isActive() ? 'Active' : 'Inactive' }}</span></td>
                                    <td><div class="editable-table__actions"><button type="button" data-edit-user data-action="{{ route('admin.users.update', $user) }}" data-name="{{ $user->name }}" data-email="{{ $user->email }}" data-role="{{ $user->role->value }}">Edit</button>@if ($user->isActive())<form method="POST" action="{{ route('admin.users.deactivate', $user) }}">@csrf @method('PATCH')<button type="submit">Deactivate</button></form>@endif</div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="editable-table-empty" data-table-empty hidden>No users match your search.</p>
                </div>
            </div>
        </section>
        <div class="modal-layer" data-modal="create-user-modal" hidden>
            <button class="modal-layer__backdrop" type="button" data-modal-close aria-label="Close dialog"></button>
            <section class="user-modal" role="dialog" aria-modal="true" aria-labelledby="create-user-title">
                <button class="user-modal__close" type="button" data-modal-close aria-label="Close dialog">×</button>
                <p class="dashboard-eyebrow">Administration</p><h2 id="create-user-title">Create user</h2><p class="user-modal__description">Give a team member access to the system.</p>
                @include('admin.users.form', ['action' => route('admin.users.store'), 'method' => 'POST', 'user' => null, 'passwordRequired' => true, 'modal' => true])
            </section>
        </div>
        <div class="modal-layer" data-modal="edit-user-modal" hidden>
            <button class="modal-layer__backdrop" type="button" data-modal-close aria-label="Close dialog"></button>
            <section class="user-modal" role="dialog" aria-modal="true" aria-labelledby="edit-user-title">
                <button class="user-modal__close" type="button" data-modal-close aria-label="Close dialog">×</button>
                <p class="dashboard-eyebrow">Administration</p><h2 id="edit-user-title">Edit user</h2><p class="user-modal__description">Update account details and permissions.</p>
                <form class="user-modal__form" method="POST" data-edit-user-form>
                    @csrf @method('PUT')
                    <label for="edit-name">Name</label><input id="edit-name" name="name" required>
                    <label for="edit-email">Email</label><input id="edit-email" name="email" type="email" required>
                    <label for="edit-role">Role</label><select id="edit-role" name="role" required><option value="admin">Admin</option><option value="field_staff">Field staff</option></select>
                    <button class="user-management__primary-action" type="submit">Save changes</button>
                </form>
            </section>
        </div>
    </x-dashboard-shell>
</body>
</html>
