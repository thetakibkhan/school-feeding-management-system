<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official reports</title>
    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])
</head>
<body>
    <x-dashboard-shell active="Official reports">
        <section class="user-management">
            <div class="user-management__heading">
                <div>
                    <p class="dashboard-eyebrow">Report generator</p>
                    <h2>Official reports</h2>
                    <p>Choose a month and one of the five supplied official forms.</p>
                </div>
            </div>

            @if ($errors->any())
                <div class="user-management__notice user-management__notice--error" role="alert">{{ $errors->first() }}</div>
            @endif
            <section class="editable-table-card demand-setup__card">
                <div class="school-detail-card__heading">
                    <div>
                        <p class="dashboard-eyebrow">Reporting period</p>
                        <h3>Selected month</h3>
                    </div>
                    <form method="GET" action="{{ route('admin.reports.index') }}" class="demand-setup__filter">
                        <label for="report-month">Month</label>
                        <input id="report-month" name="month" type="month" value="{{ $month }}" required>
                        <button type="submit">Show forms</button>
                    </form>
                </div>
                <div class="demand-setup__body">
                    <table class="editable-table">
                        <thead><tr><th>Official form</th><th>Purpose</th><th>Action</th></tr></thead>
                        <tbody>
                            <tr><td>Form 4</td><td>School daily delivery record</td><td><a class="demand-setup__link" href="{{ route('admin.reports.form-four', ['month' => $month]) }}">Open preview</a></td></tr>
                    <tr><td>Form 7</td><td>Upazila school supply statement</td><td><a class="demand-setup__link" href="{{ route('admin.reports.form-seven', ['month' => $month]) }}">Open preview</a></td></tr>
                    <tr><td>Form 10</td><td>Supplier invoice</td><td><a class="demand-setup__link" href="{{ route('admin.reports.form-ten', ['month' => $month]) }}">Open preview</a></td></tr>
                            <tr><td>Form 12</td><td>School monthly stock record</td><td><a class="demand-setup__link" href="{{ route('admin.reports.stock.information', ['form' => 12, 'month' => $month]) }}">Select month and school</a></td></tr>
                            <tr><td>Form 13</td><td>Upazila monthly stock statement</td><td><a class="demand-setup__link" href="{{ route('admin.reports.stock.information', ['form' => 13, 'month' => $month]) }}">Enter information</a></td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </section>
    </x-dashboard-shell>
</body>
</html>
