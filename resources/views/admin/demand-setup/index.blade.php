<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demand setup</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-dashboard-shell active="Demand setup">
        <section class="user-management demand-setup">
            <div class="user-management__heading">
                <div>
                    <p class="dashboard-eyebrow">Administration</p>
                    <h2>Demand setup</h2>
                    <p>Manage rations, non-working dates, and daily Bun, Egg, and Banana schedules.</p>
                </div>
            </div>

            @if (session('status'))
                <div class="user-management__notice" role="status">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="user-management__notice user-management__notice--error" role="alert">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="user-management__notice user-management__notice--error" role="alert">{{ $errors->first() }}</div>
            @endif

            <div class="demand-setup__grid">
                <section class="editable-table-card demand-setup__card">
                    <div class="school-detail-card__heading"><div><p class="dashboard-eyebrow">Item specification</p><h3>Ration history</h3></div></div>
                    <div class="demand-setup__body">
                        <form class="demand-setup__form" method="POST" action="{{ route('admin.demand-setup.rations.store') }}">
                            @csrf
                            <label>Food item <select name="food_item_id" required><option value="">Choose item</option>@foreach ($foodItems as $foodItem)<option value="{{ $foodItem->id }}" @selected(old('food_item_id') == $foodItem->id)>{{ $foodItem->name }}</option>@endforeach</select></label>
                            <label>Ration (grams) <input type="number" min="1" name="ration_grams" value="{{ old('ration_grams') }}" required></label>
                            <label>Effective start date <input type="date" name="effective_start_date" value="{{ old('effective_start_date') }}" required></label>
                            <button class="user-management__primary-action" type="submit">Add ration record</button>
                        </form>
                        <table class="editable-table demand-setup__table"><thead><tr><th>Item</th><th>Grams</th><th>Effective from</th></tr></thead><tbody>@foreach ($rationSettings as $setting)<tr><td>{{ $setting->foodItem->name }}</td><td>{{ $setting->ration_grams }}g</td><td>{{ $setting->effective_start_date->format('d M Y') }}</td></tr>@endforeach</tbody></table>
                    </div>
                </section>

                <section class="editable-table-card demand-setup__card">
                    <div class="school-detail-card__heading"><div><p class="dashboard-eyebrow">Calendar exception</p><h3>Non-working dates</h3></div></div>
                    <div class="demand-setup__body">
                        <form class="demand-setup__form" method="POST" action="{{ route('admin.demand-setup.non-working-dates.store') }}">
                            @csrf
                            <label>Date <input type="date" name="date" value="{{ old('date') }}" required></label>
                            <label>Reason (optional) <input type="text" name="reason" value="{{ old('reason') }}" maxlength="255"></label>
                            <button class="user-management__primary-action" type="submit">Mark non-working</button>
                        </form>
                        <table class="editable-table demand-setup__table"><thead><tr><th>Date</th><th>Reason</th><th>Action</th></tr></thead><tbody>@forelse ($nonWorkingDates as $nonWorkingDate)<tr><td>{{ $nonWorkingDate->date->format('d M Y') }}</td><td>{{ $nonWorkingDate->reason ?: '—' }}</td><td><form method="POST" action="{{ route('admin.demand-setup.non-working-dates.destroy', $nonWorkingDate) }}" onsubmit="return confirm('Remove this non-working date?');">@csrf @method('DELETE')<button class="demand-setup__link" type="submit">Remove</button></form></td></tr>@empty<tr><td colspan="3" class="editable-table-empty">No non-working dates in this range.</td></tr>@endforelse</tbody></table>
                    </div>
                </section>
            </div>

            <section class="editable-table-card demand-setup__card demand-setup__schedule-card">
                <div class="school-detail-card__heading"><div><p class="dashboard-eyebrow">Date-wise plan</p><h3>Working schedules</h3></div><form class="demand-setup__filter" method="GET" action="{{ route('admin.demand-setup.index') }}"><input type="date" name="from" value="{{ $from }}" aria-label="From date"><input type="date" name="to" value="{{ $to }}" aria-label="To date"><button type="submit">Filter</button></form></div>
                <div class="demand-setup__body">
                    <form class="demand-setup__form demand-setup__schedule-form" method="POST" action="{{ route('admin.demand-setup.schedules.store') }}">
                        @csrf
                        <label>Date <input type="date" name="date" required></label>
                        <fieldset><legend>Scheduled items</legend>@foreach ($foodItems as $foodItem)<label class="demand-setup__check"><input type="checkbox" name="food_item_ids[]" value="{{ $foodItem->id }}"> {{ $foodItem->name }}</label>@endforeach</fieldset>
                        <button class="user-management__primary-action" type="submit">Add working date</button>
                    </form>
                    <div class="editable-table-scroll"><table class="editable-table"><thead><tr><th>Date</th><th>Bun</th><th>Boiled Egg</th><th>Banana</th><th>Action</th></tr></thead><tbody>@forelse ($schedules as $schedule)@php($itemKeys = $schedule->items->pluck('key')->all())<tr><td>{{ $schedule->date->format('d M Y') }}</td><td>{{ in_array('bun', $itemKeys, true) ? 'Scheduled' : '—' }}</td><td>{{ in_array('boiled_egg', $itemKeys, true) ? 'Scheduled' : '—' }}</td><td>{{ in_array('banana', $itemKeys, true) ? 'Scheduled' : '—' }}</td><td><details class="demand-setup__edit"><summary>Edit</summary><form class="demand-setup__inline-form" method="POST" action="{{ route('admin.demand-setup.schedules.update', $schedule) }}">@csrf @method('PUT')<input type="date" name="date" value="{{ $schedule->date->toDateString() }}" required>@foreach ($foodItems as $foodItem)<label class="demand-setup__check"><input type="checkbox" name="food_item_ids[]" value="{{ $foodItem->id }}" @checked(in_array($foodItem->key, $itemKeys, true))> {{ $foodItem->name }}</label>@endforeach<button type="submit">Save</button></form></details><form method="POST" action="{{ route('admin.demand-setup.schedules.destroy', $schedule) }}" onsubmit="return confirm('Delete this working schedule?');">@csrf @method('DELETE')<button class="demand-setup__link" type="submit">Delete</button></form></td></tr>@empty<tr><td colspan="5" class="editable-table-empty">No working schedules in this range.</td></tr>@endforelse</tbody></table></div>
                </div>
            </section>
        </section>
    </x-dashboard-shell>
</body>
</html>
