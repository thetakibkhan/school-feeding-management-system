<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])
</head>
<body>
    <x-dashboard-shell active="Dashboard">
        @if (auth()->user()->isAdmin())
            <section class="dashboard-overview">
                <header class="dashboard-overview__heading">
                    <div>
                        <p class="dashboard-eyebrow">Upazila overview</p>
                        <h2>Today’s delivery</h2>
                        <p>{{ \Illuminate\Support\Carbon::parse($today)->format('d M Y') }}</p>
                    </div>
                    <a class="daily-report__button" href="{{ route('reports.daily-delivery.index', ['date' => $today]) }}">Open daily report</a>
                </header>

                @if ($dashboardStatus === 'off_day')
                    <p class="daily-report__notice" role="status">Today is an Off-day. Demand is zero for all items.</p>
                @elseif ($dashboardStatus === 'not_set_up')
                    <p class="daily-report__notice" role="status">Date not set up. No working schedule or non-working date is configured for today.</p>
                @else
                    <div class="dashboard-metrics">
                        @foreach ($dashboardReport['foodItems'] as $foodItem)
                            @php($totals = $dashboardReport['totals'][$foodItem->key])
                            <article class="dashboard-metric-card">
                                <div class="dashboard-metric-card__heading">
                                    <h3>{{ $foodItem->name }}</h3>
                                    <span>{{ $foodItem->unit }}</span>
                                </div>
                                <dl>
                                    <div><dt>Demand</dt><dd>{{ number_format($totals['demand']) }}</dd></div>
                                    <div><dt>Delivered</dt><dd>{{ number_format($totals['delivered']) }}</dd></div>
                                    <div><dt>Shortfall</dt><dd>{{ number_format($totals['shortfall']) }}</dd></div>
                                    <div><dt>Excess</dt><dd>{{ number_format($totals['excess']) }}</dd></div>
                                </dl>
                            </article>
                        @endforeach
                    </div>

                    <section class="dashboard-shortfalls">
                        <div class="dashboard-shortfalls__heading">
                            <div><p class="dashboard-eyebrow">Needs attention</p><h3>Schools with a shortfall</h3></div>
                            <span>{{ $shortfallSchools->total() }} schools</span>
                        </div>
                        <div class="daily-report__table-wrap">
                            <table class="daily-report__table dashboard-shortfalls__table">
                                <thead><tr><th scope="col">School code</th><th scope="col">School</th><th scope="col">Shortfall by item</th><th scope="col">Entry</th></tr></thead>
                                <tbody>
                                    @forelse ($shortfallSchools as $row)
                                        <tr>
                                            <td>{{ $row['school']->school_code }}</td>
                                            <td><span class="font-bangla">{{ $row['school']->name }}</span></td>
                                            <td>
                                                <div class="dashboard-shortfalls__items">
                                                    @foreach ($dashboardReport['foodItems'] as $foodItem)
                                                        @if ($row['items'][$foodItem->key]['shortfall'] > 0)
                                                            <span>{{ $foodItem->name }}: {{ number_format($row['items'][$foodItem->key]['shortfall']) }}</span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td>{{ $row['has_delivery'] ? 'Entered' : 'No entry yet' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="daily-report__empty">No schools have a shortfall today.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="school-pagination dashboard-list-pagination">
                            {{ $shortfallSchools->onEachSide(1)->links() }}
                        </div>
                    </section>
                @endif
            </section>
        @else
            <section class="dashboard-overview">
                <header class="dashboard-overview__heading">
                    <div>
                        <p class="dashboard-eyebrow">Field staff</p>
                        <h2>Today’s entries</h2>
                        <p>{{ \Illuminate\Support\Carbon::parse($today)->format('d M Y') }}</p>
                    </div>
                    <a class="daily-report__button" href="{{ route('reports.daily-delivery.index', ['date' => $today]) }}">View daily report</a>
                </header>

                @if ($dashboardStatus === 'off_day')
                    <p class="daily-report__notice" role="status">Today is an Off-day. Delivery entry is unavailable.</p>
                @elseif ($dashboardStatus === 'not_set_up')
                    <p class="daily-report__notice" role="status">Date not set up. Ask Admin to configure today’s schedule.</p>
                @endif

                <section class="dashboard-shortfalls">
                    <div class="dashboard-shortfalls__heading">
                        <div><p class="dashboard-eyebrow">Your work</p><h3>My entries today</h3></div>
                        <a class="daily-report__text-link" href="{{ route('field-staff.deliveries.index') }}">View all entries</a>
                    </div>
                    <div class="daily-report__table-wrap">
                        <table class="daily-report__table dashboard-shortfalls__table">
                            <thead><tr><th scope="col">School code</th><th scope="col">School</th><th scope="col">Bun</th><th scope="col">Boiled Egg</th><th scope="col">Banana</th></tr></thead>
                            <tbody>
                                @forelse ($ownDeliveries as $delivery)
                                    <tr>
                                        <td>{{ $delivery->school->school_code }}</td>
                                        <td><span class="font-bangla">{{ $delivery->school->name }}</span></td>
                                        <td>{{ number_format($delivery->bun_quantity) }}</td>
                                        <td>{{ number_format($delivery->egg_quantity) }}</td>
                                        <td>{{ number_format($delivery->banana_quantity) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="daily-report__empty">No delivery entries from you today.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="school-pagination dashboard-list-pagination">
                        {{ $ownDeliveries->onEachSide(1)->links() }}
                    </div>
                </section>
            </section>
        @endif
    </x-dashboard-shell>
</body>
</html>
