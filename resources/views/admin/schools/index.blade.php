<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School management</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-dashboard-shell active="Schools">
        <section class="user-management school-management" data-editable-table data-table-search-keys="name,schoolCode,emisCode">
            <div class="user-management__heading">
                <div>
                    <p class="dashboard-eyebrow">Administration</p>
                    <h2>Schools</h2>
                    <p>Maintain the schools and effective student counts used for demand.</p>
                </div>
                <button class="user-management__primary-action" type="button" data-modal-open="create-school-modal">+ Add school</button>
            </div>

            @if (session('status'))
                <div class="user-management__notice" role="status">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="user-management__notice user-management__notice--error" role="alert">{{ session('error') }}</div>
            @endif

            <div class="editable-table-card">
                <div class="editable-table-toolbar">
                    <form method="GET" action="{{ route('admin.schools.index') }}" class="editable-table-search">
                        <span aria-hidden="true">⌕</span>
                        <input type="search" name="search" placeholder="Search school, code, or EMIS" value="{{ $search }}" data-table-search>
                    </form>
                    <span class="editable-table-count"><strong data-table-visible-count>{{ $schools->count() }}</strong> schools</span>
                </div>
                <div class="editable-table-scroll">
                    <table class="editable-table">
                        <thead>
                            <tr>
                                <th><button type="button" data-table-sort="name">School <span>↕</span></button></th>
                                <th><button type="button" data-table-sort="schoolCode">School code <span>↕</span></button></th>
                                <th><button type="button" data-table-sort="emisCode">EMIS code <span>↕</span></button></th>
                                <th><button type="button" data-table-sort="students">Current students <span>↕</span></button></th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody data-table-body>
                            @forelse ($schools as $school)
                                @php($currentCount = $school->studentCounts->first())
                                <tr data-table-row data-name="{{ mb_strtolower($school->name) }}" data-school-code="{{ strtolower($school->school_code) }}" data-emis-code="{{ strtolower($school->emis_code) }}" data-students="{{ $currentCount?->student_count ?? 0 }}">
                                    <td><div class="user-cell"><span class="user-cell__avatar">বিদ্যালয়</span><strong>{{ $school->name }}</strong></div></td>
                                    <td class="editable-table__muted">{{ $school->school_code }}</td>
                                    <td class="editable-table__muted">{{ $school->emis_code }}</td>
                                    <td>{{ $currentCount ? number_format($currentCount->student_count) : 'No count yet' }}</td>
                                    <td>
                                        <div class="editable-table__actions">
                                            <a href="{{ route('admin.schools.show', $school) }}">View</a>
                                            <button type="button" data-edit-school data-action="{{ route('admin.schools.update', $school) }}" data-name="{{ $school->name }}" data-school-code="{{ $school->school_code }}" data-emis-code="{{ $school->emis_code }}">Edit</button>
                                            <form method="POST" action="{{ route('admin.schools.destroy', $school) }}" onsubmit="return confirm('Delete this school and its unused student-count history?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="editable-table-empty">No schools found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <p class="editable-table-empty" data-table-empty hidden>No schools match your search.</p>
                </div>
            </div>
        </section>

        <div class="modal-layer" data-modal="create-school-modal" @if ($errors->any()) data-open-on-error @else hidden @endif>
            <button class="modal-layer__backdrop" type="button" data-modal-close aria-label="Close dialog"></button>
            <section class="user-modal school-modal" role="dialog" aria-modal="true" aria-labelledby="create-school-title">
                <button class="user-modal__close" type="button" data-modal-close aria-label="Close dialog">×</button>
                <p class="dashboard-eyebrow">School management</p>
                <h2 id="create-school-title">Add school</h2>
                <p class="user-modal__description">Add the supplied Bangla school name and its first student count.</p>
                @include('admin.schools.form', ['action' => route('admin.schools.store'), 'method' => 'POST', 'school' => null])
            </section>
        </div>

        <div class="modal-layer" data-modal="edit-school-modal" hidden>
            <button class="modal-layer__backdrop" type="button" data-modal-close aria-label="Close dialog"></button>
            <section class="user-modal school-modal" role="dialog" aria-modal="true" aria-labelledby="edit-school-title">
                <button class="user-modal__close" type="button" data-modal-close aria-label="Close dialog">×</button>
                <p class="dashboard-eyebrow">School management</p>
                <h2 id="edit-school-title">Edit school</h2>
                <p class="user-modal__description">Update the school identity details. Student counts are managed from the school record.</p>
                <form class="user-modal__form" method="POST" data-edit-school-form>
                    @csrf
                    @method('PUT')
                    <label for="edit-school-name">School name</label>
                    <input id="edit-school-name" name="name" required>
                    <label for="edit-school-code">School code</label>
                    <input id="edit-school-code" name="school_code" required>
                    <label for="edit-emis-code">EMIS code</label>
                    <input id="edit-emis-code" name="emis_code" required>
                    <button class="user-management__primary-action" type="submit">Save changes</button>
                </form>
            </section>
        </div>
    </x-dashboard-shell>
</body>
</html>
