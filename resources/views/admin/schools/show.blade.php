<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $school->name }}</title>
    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])
</head>
<body>
    <x-dashboard-shell active="Schools">
        <section class="user-management school-management">
            <div class="user-management__heading">
                <div>
                    <p class="dashboard-eyebrow">School record</p>
                    <h2 class="font-bangla">{{ $school->name }}</h2>
                    <p>School code: {{ $school->school_code }} · EMIS code: {{ $school->emis_code }}</p>
                </div>
                <a class="user-management__primary-action" href="{{ route('admin.schools.index') }}">Back to schools</a>
            </div>

            @if (session('status'))
                <div class="user-management__notice" role="status">{{ session('status') }}</div>
            @endif

            <div class="school-detail-grid">
                <section class="editable-table-card school-detail-card">
                    <div class="school-detail-card__heading">
                        <div><p class="dashboard-eyebrow">Student count</p><h3>Effective-dated history</h3></div>
                        <button class="user-management__primary-action" type="button" data-modal-open="add-student-count-modal">+ Add count</button>
                    </div>
                    <div class="editable-table-scroll">
                        <table class="editable-table school-history-table">
                            <thead><tr><th>Effective from</th><th>Students</th></tr></thead>
                            <tbody>
                                @forelse ($school->studentCounts as $studentCount)
                                    <tr><td>{{ $studentCount->effective_start_date->format('d M Y') }}</td><td>{{ number_format($studentCount->student_count) }}</td></tr>
                                @empty
                                    <tr><td colspan="2" class="editable-table-empty">No student count has been added.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <p class="school-detail-card__note">Demand and reports use the latest count effective on or before their date. A school without one is excluded for that date.</p>
                </section>
            </div>
        </section>

        <div class="modal-layer" data-modal="add-student-count-modal" @if ($errors->any()) data-open-on-error @else hidden @endif>
            <button class="modal-layer__backdrop" type="button" data-modal-close aria-label="Close dialog"></button>
            <section class="user-modal school-modal" role="dialog" aria-modal="true" aria-labelledby="add-student-count-title">
                <button class="user-modal__close" type="button" data-modal-close aria-label="Close dialog">×</button>
                <p class="dashboard-eyebrow font-bangla">{{ $school->name }}</p>
                <h2 id="add-student-count-title">Add student count</h2>
                <p class="user-modal__description">Use the date when this count starts applying. Past dates are allowed.</p>
                @if ($errors->any())
                    <ul class="school-form-errors">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                @endif
                <form method="POST" action="{{ route('admin.schools.student-counts.store', $school) }}" class="user-modal__form">
                    @csrf
                    <label for="student-count">Student count</label>
                    <input id="student-count" name="student_count" type="number" min="1" value="{{ old('student_count') }}" required>
                    <label for="effective-start-date">Effective start date</label>
                    <input id="effective-start-date" name="effective_start_date" type="date" value="{{ old('effective_start_date') }}" required>
                    <button class="user-management__primary-action" type="submit">Add count</button>
                </form>
            </section>
        </div>
    </x-dashboard-shell>
</body>
</html>
