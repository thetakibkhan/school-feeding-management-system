@if ($errors->any())
    <ul class="school-form-errors">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<form method="POST" action="{{ $action }}" class="user-modal__form">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <label for="school-name">School name</label>
    <input id="school-name" name="name" value="{{ old('name', $school?->name) }}" required>

    <label for="school-code">School code</label>
    <input id="school-code" name="school_code" value="{{ old('school_code', $school?->school_code) }}" required>

    <label for="emis-code">EMIS code</label>
    <input id="emis-code" name="emis_code" value="{{ old('emis_code', $school?->emis_code) }}" required>

    <label for="principal-name">Principal name (optional)</label>
    <input id="principal-name" name="principal_name" value="{{ old('principal_name', $school?->principal_name) }}">

    <label for="principal-mobile">Principal mobile (optional)</label>
    <input id="principal-mobile" name="principal_mobile" type="tel" inputmode="tel" value="{{ old('principal_mobile', $school?->principal_mobile) }}">

    <label for="student-count">Initial student count</label>
    <input id="student-count" name="student_count" type="number" min="1" value="{{ old('student_count') }}" required>

    <label for="effective-start-date">Effective start date</label>
    <input id="effective-start-date" name="effective_start_date" type="date" value="{{ old('effective_start_date') }}" required>

    <button class="user-management__primary-action" type="submit">Save school</button>
</form>
