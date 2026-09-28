<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Delivery Report</title>
    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])
</head>
<body class="daily-report-page">
    <x-dashboard-shell active="Daily report" :display-date="\Illuminate\Support\Carbon::parse($report['date'])->format('D, d M Y')">
        <section class="daily-report">
            <header class="daily-report__heading">
                <div>
                    <p class="dashboard-eyebrow">School Feeding Programme</p>
                    <h2>Daily Delivery Report</h2>
                    <p>Compare planned demand with actual deliveries for each school.</p>
                </div>
            </header>

            @if ($errors->has('date'))
                <p class="daily-report__notice daily-report__notice--error" role="alert">Choose a valid report date.</p>
            @endif

            <div class="daily-report__controls">
                <form method="GET" action="{{ route('reports.daily-delivery.index') }}" class="daily-report__date-form">
                    <label for="report-date">Report date</label>
                    <input id="report-date" type="date" name="date" value="{{ $report['date'] }}" required>
                    <button class="daily-report__button" type="submit">Show report</button>
                </form>
                <div class="daily-report__actions">
                    <button class="daily-report__button daily-report__button--secondary" type="button" data-print-report>Print</button>
                    <a class="daily-report__button daily-report__button--secondary" href="{{ route('reports.daily-delivery.export', ['date' => $report['date']]) }}">Export CSV for Excel</a>
                </div>
            </div>

            <section class="daily-report__card" aria-labelledby="daily-report-title">
                <div class="daily-report__card-heading">
                    <div>
                        <p class="dashboard-eyebrow">{{ \Illuminate\Support\Carbon::parse($report['date'])->format('l, d F Y') }}</p>
                        <h3 id="daily-report-title">School demand and delivery</h3>
                    </div>
                    @if ($report['status'] === 'working')
                        <span class="daily-report__badge">Working day</span>
                    @endif
                </div>

                @if ($report['status'] === 'off_day')
                    <p class="daily-report__notice" role="status">Off-day. Demand is zero for every item.</p>
                @elseif ($report['status'] === 'not_set_up')
                    <p class="daily-report__notice" role="status">Date not set up. No working schedule or non-working date is configured.</p>
                @else
                    @include('reports.daily-delivery._table', ['report' => $report])
                @endif
            </section>
            @if ($canViewChalanPhotos)
                @include('field-staff.deliveries._photo-modal')
            @endif
        </section>
    </x-dashboard-shell>
</body>
</html>
