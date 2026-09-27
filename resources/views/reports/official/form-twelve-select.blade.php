<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form 12 report</title>
    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])
</head>
<body>
    <x-dashboard-shell active="Official reports">
        <section class="user-management">
            <div class="user-management__heading">
                <div><p class="dashboard-eyebrow">Official Form 12</p><h2>School monthly stock report</h2></div>
                <a class="demand-setup__link" href="{{ route('admin.reports.index', ['month' => $month]) }}">All forms</a>
            </div>
            @if ($errors->any())
                <div class="user-management__notice user-management__notice--error" role="alert">{{ $errors->first() }}</div>
            @endif
            <section class="editable-table-card demand-setup__card">
                <div class="school-detail-card__heading"><div><p class="dashboard-eyebrow">Report selection</p><h3>Select month and school</h3></div></div>
                <div class="demand-setup__body">
                    <form method="GET" action="{{ route('admin.reports.stock.preview', ['form' => 12]) }}" class="demand-setup__filter form-twelve-selection">
                        <label for="form12-month">Reporting month / year</label>
                        <input id="form12-month" name="month" type="month" value="{{ $month }}" required>
                        <label for="form12-school">School</label>
                        <select id="form12-school" name="school_id">
                            <option value="all" @selected($schoolId === 'all')>All schools</option>
                            @foreach ($schools as $school)
                                <option value="{{ $school->id }}" @selected($schoolId === (string) $school->id)>{{ $school->school_code }} · {{ $school->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit">Open preview</button>
                    </form>
                </div>
            </section>
        </section>
    </x-dashboard-shell>
    <style>
        .form-twelve-selection{display:flex;flex-wrap:wrap;align-items:end;gap:1rem}.form-twelve-selection label{color:#ddd}.form-twelve-selection select{min-width:20rem;max-width:100%;padding:.6rem;border:1px solid #394052;border-radius:.55rem;background:#151820;color:#fff}
    </style>
</body>
</html>
