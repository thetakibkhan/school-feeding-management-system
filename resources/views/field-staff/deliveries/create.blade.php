<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add delivery</title>
    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])
</head>
<body>
    <x-dashboard-shell active="My entries">
        <section class="user-management delivery-management">
            <div class="user-management__heading">
                <div><p class="dashboard-eyebrow">Daily delivery</p><h2>Add delivery</h2><p>Enter actual quantities delivered and attach the chalan photo.</p></div>
                <a class="delivery-back-link" href="{{ route('field-staff.deliveries.index') }}">Back to my entries</a>
            </div>

            @if ($errors->any())
                <div class="user-management__notice user-management__notice--error" role="alert">{{ $errors->first() }}</div>
            @endif

            <section class="editable-table-card delivery-form-card">
                <div class="school-detail-card__heading"><div><p class="dashboard-eyebrow">Step 1</p><h3>Select date and school</h3></div></div>
                <form class="delivery-form" method="GET" action="{{ route('field-staff.deliveries.create') }}">
                    <label for="delivery-date">Delivery date</label>
                    <input id="delivery-date" type="date" name="date" max="{{ today()->toDateString() }}" value="{{ old('date', $selectedDate) }}" required>
                    <label for="delivery-school">School</label>
                    <select id="delivery-school" name="school_id" required>
                        <option value="">Choose a school</option>
                        @foreach ($schools as $school)
                            <option value="{{ $school->id }}" @selected((string) old('school_id', $selectedSchoolId) === (string) $school->id)>{{ $school->school_code }} — {{ $school->name }}</option>
                        @endforeach
                    </select>
                    <button class="user-management__primary-action" type="submit">Load schedule</button>
                </form>
            </section>

            @if ($schedule)
                <section class="editable-table-card delivery-form-card">
                    <div class="school-detail-card__heading"><div><p class="dashboard-eyebrow">Step 2 · {{ \Illuminate\Support\Carbon::parse($selectedDate)->format('d M Y') }}</p><h3>Record delivered quantities</h3><p>Enter what arrived, not the demand estimate. Zero is allowed for an individual item.</p></div></div>
                    <div class="delivery-schedule-note">
                        <strong>Scheduled:</strong>
                        @foreach ($schedule->items as $item)
                            <span>{{ $item->name }}</span>
                        @endforeach
                        <small>Demand guidance is calculated in packets/pieces from the effective student count.</small>
                    </div>
                    <form class="delivery-form" method="POST" action="{{ route('field-staff.deliveries.store') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        <input type="hidden" name="school_id" value="{{ $selectedSchoolId }}">
                        @foreach ($foodItems as $item)
                            @php($field = $quantityFields[$item->key] ?? null)
                            @if ($field)
                                <div class="delivery-quantity-row">
                                    <label for="{{ $field }}">{{ $item->name }} <small>({{ $item->unit }})</small></label>
                                    <input id="{{ $field }}" type="number" name="{{ $field }}" min="0" step="1" value="{{ old($field, 0) }}" required>
                                    <span class="delivery-demand">Demand: {{ number_format($demand[$item->key] ?? 0) }} {{ $item->unit }}</span>
                                    @unless ($schedule->items->contains('id', $item->id))<small class="delivery-unscheduled">Not scheduled · record only if excess was delivered</small>@endunless
                                </div>
                            @endif
                        @endforeach
                        <label for="chalan-number">Chalan number <span class="delivery-required">Required</span></label>
                        <input id="chalan-number" type="text" name="chalan_number" value="{{ old('chalan_number') }}" maxlength="100" required>
                        <label for="chalan-date">Chalan date <span class="delivery-required">Required</span></label>
                        <input id="chalan-date" type="date" name="chalan_date" value="{{ old('chalan_date', $selectedDate) }}" required>
                        <label for="chalan-photo">Chalan photo <span class="delivery-required">Required</span></label>
                        <input id="chalan-photo" type="file" name="chalan_photo" accept="image/jpeg,image/png,image/webp" required>
                        <p class="delivery-help">JPG, PNG, or WebP · maximum 5 MB. Only you can view this photo.</p>
                        <button class="user-management__primary-action" type="submit">Save delivery</button>
                    </form>
                </section>
            @endif
        </section>
    </x-dashboard-shell>
</body>
</html>
