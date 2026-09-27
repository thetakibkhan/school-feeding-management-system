<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form {{ $form }} report information</title>
    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])
</head>
<body>
    <x-dashboard-shell active="Official reports">
        <section class="user-management">
            <div class="user-management__heading">
                <div><p class="dashboard-eyebrow">Official Form {{ $form }}</p><h2>Report information</h2><p>Enter actual stock facts for {{ $month }}. A school receipt is not proof of student distribution.</p></div>
                <a class="demand-setup__link" href="{{ route('admin.reports.index', ['month' => $month]) }}">All forms</a>
            </div>
            @if ($errors->any()) <div class="user-management__notice user-management__notice--error" role="alert">{{ $errors->first() }}</div> @endif
            @if (session('status')) <div class="user-management__notice" role="status">{{ session('status') }}</div> @endif
            <section class="editable-table-card demand-setup__card">
                <div class="school-detail-card__heading"><div><p class="dashboard-eyebrow">Period metadata</p><h3>Form {{ $form }} · {{ $month }}</h3></div></div>
                <div class="demand-setup__body">
                    <form method="POST" action="{{ route('admin.reports.stock.information.update', ['form' => $form]) }}" class="stock-info-form">
                        @csrf @method('PUT')
                        <input type="hidden" name="month" value="{{ $month }}">
                        <label>District<input name="district_name" value="{{ old('district_name', $period?->district_name ?? 'চট্টগ্রাম') }}" required maxlength="255"></label>
                        <label>Upazila<input name="upazila_name" value="{{ old('upazila_name', $period?->upazila_name ?? 'আনোয়ারা') }}" required maxlength="255"></label>
                        @if ($form === '13')
                            <label>Supplier/contractor<input name="supplier_name" value="{{ old('supplier_name', $period?->supplier_name ?? $supplier) }}" required maxlength="255"></label>
                        @endif
                        <button type="submit">Save report information</button>
                    </form>
                </div>
            </section>

            <section class="editable-table-card demand-setup__card">
                <div class="school-detail-card__heading"><div><p class="dashboard-eyebrow">School stock inputs</p><h3>{{ $completedCount }} / {{ $report['school_count'] }} schools complete</h3></div>
                    @if ($period)<a class="demand-setup__link" href="{{ route('admin.reports.stock.preview', ['form' => $form, 'month' => $month]) }}">Open preview</a>@endif
                </div>
                <div class="demand-setup__body">
                    <form method="GET" action="{{ route('admin.reports.stock.information', ['form' => $form]) }}" class="stock-info-form">
                        <label>Month<input type="month" name="month" value="{{ $month }}" required></label>
                        <label>School<select name="school_id" onchange="this.form.submit()">
                            @foreach ($report['schools'] as $row)
                                <option value="{{ $row['school']->id }}" @selected($selected && $selected['school']->id === $row['school']->id)>{{ $row['school']->school_code }} · {{ $row['school']->name }}</option>
                            @endforeach
                        </select></label>
                        <button type="submit">Show school</button>
                    </form>
                    @if ($selected)
                        <p class="stock-info-note">Received Bun/Egg/Banana comes from saved delivery entries. {{ $selected['missing_delivery_count'] }} scheduled delivery entries are still missing for this school. Enter actual opening stock and actual distributed amounts. For Form 12, boys plus girls must match the school student total before final print/PDF. Enter 0 only when confirmed.</p>
                        <form method="POST" action="{{ route('admin.reports.stock.schools.update', ['form' => $form, 'school' => $selected['school']]) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="month" value="{{ $month }}">
                            <div class="stock-info-form">
                                <label>Boys<input type="number" name="boy_count" min="0" value="{{ old('boy_count', $selected['input']?->boy_count) }}"></label>
                                <label>Girls<input type="number" name="girl_count" min="0" value="{{ old('girl_count', $selected['input']?->girl_count) }}"></label>
                                <label>Union<input name="union_name" value="{{ old('union_name', $selected['input']?->union_name) }}" maxlength="255"></label>
                                <label>Cluster<input name="cluster_name" value="{{ old('cluster_name', $selected['input']?->cluster_name) }}" maxlength="255"></label>
                            </div>
                            <div class="stock-info-table-wrap"><table class="editable-table stock-info-table">
                                <thead><tr><th>Item</th><th>Opening stock</th><th>Received</th><th>Actually distributed</th><th>Closing stock</th></tr></thead>
                                <tbody>
                                @foreach (($form === '12' ? ['milk' => 'UHT Milk', 'biscuit' => 'Fortified Biscuit', 'bun' => 'Bun', 'egg' => 'Boiled Egg', 'banana' => 'Banana'] : ['bun' => 'Bun', 'egg' => 'Boiled Egg', 'banana' => 'Banana']) as $key => $label)
                                    <tr><th>{{ $label }}</th>
                                        <td><input type="number" min="0" name="{{ $key }}_opening" aria-label="{{ $label }} opening" value="{{ old($key.'_opening', data_get($selected['input'], $key.'_opening')) }}" required></td>
                                        <td>@if (in_array($key, ['milk', 'biscuit']))<input type="number" min="0" name="{{ $key }}_received" aria-label="{{ $label }} received" value="{{ old($key.'_received', data_get($selected['input'], $key.'_received')) }}" required>@else{{ $selected['items'][$key]['received'] }} <small>from deliveries</small>@endif</td>
                                        <td><input type="number" min="0" name="{{ $key }}_distributed" aria-label="{{ $label }} distributed" value="{{ old($key.'_distributed', data_get($selected['input'], $key.'_distributed')) }}" required></td>
                                        <td>{{ $selected['items'][$key]['closing'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table></div>
                            <button type="submit" class="stock-info-save">Save school stock</button>
                        </form>
                    @endif
                </div>
            </section>
        </section>
    </x-dashboard-shell>
    <style>
        .stock-info-form{display:flex;flex-wrap:wrap;align-items:end;gap:1rem;margin:1rem 0}.stock-info-form label{display:grid;gap:.35rem;min-width:12rem;flex:1;color:#ddd}.stock-info-form input,.stock-info-form select,.stock-info-table input{width:100%;padding:.6rem;border:1px solid #394052;border-radius:.55rem;background:#151820;color:#fff}.stock-info-form button,.stock-info-save{padding:.7rem 1rem;border:0;border-radius:.6rem;background:#3411c6;color:white;cursor:pointer}.stock-info-note{color:#c6c8d0;line-height:1.5}.stock-info-table-wrap{overflow-x:auto}.stock-info-table input{min-width:5rem}.stock-info-table small{display:block;color:#aaa}.stock-info-save{margin:1rem 0}
    </style>
</body>
</html>
