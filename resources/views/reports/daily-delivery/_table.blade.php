<div class="daily-report__table-wrap">
    <table class="daily-report__table">
        <thead>
            <tr>
                <th scope="col" rowspan="2">School code</th>
                <th scope="col" rowspan="2">EMIS code</th>
                <th scope="col" rowspan="2">School</th>
                @foreach ($report['foodItems'] as $foodItem)
                    <th scope="colgroup" colspan="4">{{ $foodItem->name }}</th>
                @endforeach
                <th scope="col" rowspan="2">Entry status</th>
            </tr>
            <tr>
                @foreach ($report['foodItems'] as $foodItem)
                    <th scope="col">Demand<br>({{ $foodItem->unit }})</th>
                    <th scope="col">Delivered<br>({{ $foodItem->unit }})</th>
                    <th scope="col">Shortfall<br>({{ $foodItem->unit }})</th>
                    <th scope="col">Excess<br>({{ $foodItem->unit }})</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($report['rows'] as $row)
                <tr>
                    <td>{{ $row['school']->school_code }}</td>
                    <td>{{ $row['school']->emis_code }}</td>
                    <td><span class="font-bangla">{{ $row['school']->name }}</span></td>
                    @foreach ($report['foodItems'] as $foodItem)
                        @foreach (['demand', 'delivered', 'shortfall', 'excess'] as $measure)
                            <td class="daily-report__number">{{ number_format($row['items'][$foodItem->key][$measure]) }}</td>
                        @endforeach
                    @endforeach
                    <td>
                        @if ($row['has_delivery'])
                            <span class="daily-report__entry-state">Entered</span>
                        @else
                            <span class="daily-report__entry-state daily-report__entry-state--missing">No entry yet</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td class="daily-report__empty" colspan="{{ 4 + ($report['foodItems']->count() * 4) }}">No schools have a student count effective on this date.</td></tr>
            @endforelse
        </tbody>
        @if ($report['rows'] !== [])
            <tfoot>
                <tr>
                    <th scope="row" colspan="3">Upazila total</th>
                    @foreach ($report['foodItems'] as $foodItem)
                        @foreach (['demand', 'delivered', 'shortfall', 'excess'] as $measure)
                            <td class="daily-report__number">{{ number_format($report['totals'][$foodItem->key][$measure]) }}</td>
                        @endforeach
                    @endforeach
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
