<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School management</title>
    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])
</head>
<body>
    <x-dashboard-shell active="Schools">
        <section class="user-management school-management">
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
                        <input type="search" name="search" placeholder="Search school, code, or EMIS" value="{{ $search }}">
                        <button type="submit" aria-label="Search schools">Search</button>
                    </form>
                    <span class="editable-table-count">Showing {{ $schools->firstItem() ?? 0 }}–{{ $schools->lastItem() ?? 0 }} of {{ $schools->total() }} schools</span>
                </div>
                <div class="editable-table-scroll">
                    <table class="editable-table school-list-table">
                        <thead>
                            <tr>
                                <th>School</th>
                                <th>School code</th>
                                <th>EMIS code</th>
                                <th>Principal</th>
                                <th>Principal mobile</th>
                                <th>Current students</th>
                                <th>90% calculated</th>
                                <th>Nearest whole number</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody data-table-body>
                            @forelse ($schools as $school)
                                @php($currentCount = $school->studentCounts->first())
                                <tr>
                                    <td><div class="user-cell"><span class="user-cell__avatar school-row-number" aria-label="School {{ ($schools->firstItem() ?? 1) + $loop->index }}">{{ ($schools->firstItem() ?? 1) + $loop->index }}</span><strong class="font-bangla">{{ $school->name }}</strong></div></td>
                                    <td class="editable-table__muted">{{ $school->school_code }}</td>
                                    <td class="editable-table__muted">{{ $school->emis_code }}</td>
                                    <td class="font-bangla">{{ $school->principal_name ?: '—' }}</td>
                                    <td>{{ $school->principal_mobile ?: '—' }}</td>
                                    <td>{{ $currentCount ? number_format($currentCount->student_count) : 'No count yet' }}</td>
                                    <td>{{ $currentCount ? number_format($currentCount->student_count * 0.9, 1) : '—' }}</td>
                                    <td>{{ $currentCount ? number_format((int) round($currentCount->student_count * 0.9)) : '—' }}</td>
                                    <td>
                                        <div class="editable-table__actions">
                                            <a href="{{ route('admin.schools.show', $school) }}">View</a>
                                            <button type="button" data-edit-school data-action="{{ route('admin.schools.update', $school) }}" data-name="{{ $school->name }}" data-school-code="{{ $school->school_code }}" data-emis-code="{{ $school->emis_code }}" data-principal-name="{{ $school->principal_name }}" data-principal-mobile="{{ $school->principal_mobile }}">Edit</button>
                                            <form method="POST" action="{{ route('admin.schools.destroy', $school) }}" onsubmit="return confirm('Delete this school and its unused student-count history?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="editable-table-empty">No schools found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="school-pagination">
                    {{ $schools->onEachSide(1)->links() }}
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
                    <label for="edit-principal-name">Principal name (optional)</label>
                    <input id="edit-principal-name" name="principal_name">
                    <label for="edit-principal-mobile">Principal mobile (optional)</label>
                    <input id="edit-principal-mobile" name="principal_mobile" type="tel" inputmode="tel">
                    <button class="user-management__primary-action" type="submit">Save changes</button>
                </form>
            </section>
        </div>
    </x-dashboard-shell>
</body>
</html>
