<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>নমুনা ফরম-০৭ — {{ $form['month_label'] }}</title>
    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('form-7/form-seven.css') }}?v={{ filemtime(public_path('form-7/form-seven.css')) }}">
</head>
<body class="form-seven-screen">
<x-app-shell>
    <div class="form-seven-shell">
        <div class="form-seven-controls">
            <div>
                <a href="{{ route('dashboard') }}" class="form-seven-back">← Dashboard</a>
                <h1>Official Form 7</h1>
                <p>Monthly school-level supply statement using the supplied Form 7 artwork.</p>
            </div>
            <form method="GET" action="{{ route('admin.reports.form-seven') }}">
                <label for="form-seven-month">Month</label>
                <input id="form-seven-month" name="month" type="month" value="{{ $form['month'] }}" required>
                <button type="submit">Show</button>
                @if ($form['missing_reasons'] === [])
                    <button type="button" onclick="window.print()">Print</button>
                @endif
            </form>
        </div>

        @if ($form['missing_reasons'] !== [])
            <div class="form-seven-controls" role="status">
                <div>
                    <h2>Required report setup is incomplete — printing is unavailable</h2>
                    <ul>
                        @foreach ($form['missing_reasons'] as $reason)
                            <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if ($form['missing_delivery_count'] > 0)
            <div class="form-seven-controls" role="status">
                <div>
                    <h2>Preview uses currently entered delivery records</h2>
                    <p>This report is generated from currently entered delivery records. Some scheduled deliveries have not yet been entered.</p>
                    <p>{{ number_format($form['missing_delivery_count']) }} expected school/date delivery records are missing. Missing records contribute zero to this preview and are not counted as challans.</p>
                </div>
            </div>
        @endif

        @php
            // Coordinates are measured from the supplied A4 PDF rendered at 130 DPI.
            // Static headings, labels, borders and signatures remain in the source-derived artwork.
            $pageRows = [1 => 16, 2 => 26, 3 => 26, 4 => 26, 5 => 16];
            $pageOffsets = [1 => 0, 2 => 16, 3 => 42, 4 => 68, 5 => 94];
            $firstBounds = [654, 703, 751, 801, 849, 899, 947, 996, 1045, 1094, 1142, 1191, 1240, 1289, 1337, 1387, 1436];
            $otherBounds = [197, 246, 294, 343, 392, 441, 489, 539, 587, 637, 685, 734, 783, 832, 880, 930, 978, 1028, 1076, 1125, 1173, 1223, 1271, 1321, 1369, 1419, 1467];
            $columnBounds = [112, 164, 430, 526, 598, 680, 751, 826, 897, 966];
            $position = static fn (int $left, int $right, int $top, int $bottom): string => sprintf(
                'left:%.5f%%;top:%.5f%%;width:%.5f%%;height:%.5f%%',
                $left / 1075 * 100,
                $top / 1521 * 100,
                ($right - $left) / 1075 * 100,
                ($bottom - $top) / 1521 * 100,
            );
            $number = static fn (int $value): string => \App\Services\FormSevenReportService::bengaliDigits(number_format($value));
        @endphp

        <div class="form-seven-sheets">
            @for ($page = 1; $page <= 6; $page++)
                <section class="form-seven-page" data-form7-page="{{ $page }}" aria-label="Form 7 page {{ $page }}">
                    <img class="form-seven-page__art" src="{{ asset('form-7/page-'.$page.'.png') }}" alt="" aria-hidden="true">

                    @if ($page === 1)
                        <div class="form-seven-title-replacement" style="background-color:#fff!important;color:#111!important;">
                            {{ $form['month_label'] }} মাসের বনরুটি (১২০ গ্রাম), সিদ্ধ ডিম (৬০ গ্রাম) ও কলা (১০০ গ্রাম) বিদ্যালয় পর্যায়ে সরবরাহের বিবরণী
                        </div>
                        <div class="form-seven-supplier-replacement" style="{{ $position(291, 420, 448, 478) }};background-color:#fff!important;color:#111!important;">{{ $form['supplier_name'] }}</div>
                    @endif

                    @if ($page <= 5)
                        @php
                            $bounds = $page === 1 ? $firstBounds : $otherBounds;
                        @endphp
                        <div class="form-seven-table-mask" style="{{ $position(112, 966, $bounds[0], $bounds[$pageRows[$page]]) }}"></div>
                        @for ($rowIndex = 0; $rowIndex < $pageRows[$page]; $rowIndex++)
                            @php
                                $recordIndex = $pageOffsets[$page] + $rowIndex;
                                $record = $form['rows'][$recordIndex] ?? null;
                                $cells = $record === null ? array_fill(0, 9, '') : [
                                    $number($recordIndex + 1),
                                    $record['school']->name,
                                    \App\Services\FormSevenReportService::bengaliDigits($record['school']->emis_code),
                                    $number($record['bun']['chalans']),
                                    $number($record['bun']['quantity']),
                                    $number($record['egg']['chalans']),
                                    $number($record['egg']['quantity']),
                                    $number($record['banana']['chalans']),
                                    $number($record['banana']['quantity']),
                                ];
                            @endphp
                            <div data-form7-row="{{ $recordIndex + 1 }}" class="form-seven-row">
                                @foreach ($cells as $column => $cell)
                                    <span class="form-seven-cell {{ $column === 0 ? 'form-seven-cell--first' : '' }} {{ $column === 1 ? 'form-seven-cell--school' : '' }} {{ $column === 2 ? 'form-seven-cell--emis' : '' }} {{ $rowIndex === 0 ? 'form-seven-cell--top' : '' }}"
                                          style="{{ $position($columnBounds[$column], $columnBounds[$column + 1], $bounds[$rowIndex], $bounds[$rowIndex + 1]) }};background-color:#fff!important;color:#111!important;">{{ $cell }}</span>
                                @endforeach
                            </div>
                        @endfor
                    @endif

                    @if ($page === 5)
                        <div class="form-seven-table-mask" style="{{ $position(112, 966, 978, 1028) }}"></div>
                        <span class="form-seven-cell form-seven-cell--first form-seven-cell--total-label"
                              style="{{ $position(112, 526, 978, 1028) }}">সর্বমোট</span>
                        @php($totalItems = [$form['totals']['bun'], $form['totals']['egg'], $form['totals']['banana']])
                        @foreach ($totalItems as $itemIndex => $item)
                            @foreach (['chalans', 'quantity'] as $measureIndex => $measure)
                                @php($column = 3 + $itemIndex * 2 + $measureIndex)
                                <span class="form-seven-cell form-seven-cell--total"
                                      style="{{ $position($columnBounds[$column], $columnBounds[$column + 1], 978, 1028) }}">{{ $number($item[$measure]) }}</span>
                            @endforeach
                        @endforeach

                        <div class="form-seven-notes">
                            <p>উপযুক্ত বিবরণ অনুযায়ী অত্র উপজেলার {{ $number(count($form['rows'])) }} টি সরকারি প্রাথমিক বিদ্যালয়ে {{ $form['month_label'] }} মাসের স্পেসিফিকেশন অনুযায়ী সরবরাহকৃত {{ $number($form['totals']['bun']['quantity']) }} প্যাকেট বনরুটি, {{ $number($form['totals']['egg']['quantity']) }} পিস সিদ্ধ ডিম ও {{ $number($form['totals']['banana']['quantity']) }} পিস কলা সরবরাহের চালানের মূল কপি অত্র কার্যালয়ে সংরক্ষিত আছে।</p>
                            <p>এমতাবস্থায়, উক্ত সরবরাহকারী ঠিকাদারকে {{ $form['month_label'] }} মাসের {{ $number($form['totals']['bun']['quantity']) }} প্যাকেট বনরুটি, {{ $number($form['totals']['egg']['quantity']) }} পিস সিদ্ধ ডিম ও {{ $number($form['totals']['banana']['quantity']) }} পিস কলা সরবরাহের বিল পরিশোধ করার সুপারিশ করা হলো।</p>
                        </div>
                    @endif
                </section>
            @endfor
        </div>
    </div>
</x-app-shell>
</body>
</html>
