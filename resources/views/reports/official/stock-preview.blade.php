<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form {{ $form }} · {{ $month }}</title>
    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('stock-forms/stock-forms.css') }}?v={{ filemtime(public_path('stock-forms/stock-forms.css')) }}">
</head>
<body>
    <x-theme-toggle />
    <div class="report-toolbar">
        <strong>Official Form {{ $form }} · {{ $month }}</strong>
        <a href="{{ route('admin.reports.stock.information', ['form' => $form, 'month' => $month, 'school_id' => $schoolId]) }}">{{ $form === '12' ? 'Report selection' : 'Report information' }}</a>
        @if ($form === '12' || $form === '13' || $ready)
            <button type="button" onclick="window.print()">Print</button>
            @if ($form === '13')
                <a href="{{ route('admin.reports.stock.pdf', ['form' => $form, 'month' => $month]) }}">Download PDF</a>
            @endif
            @if ($form === '12' && ! $ready)
                <span role="alert">Some Form 12 data is missing. This preview uses saved records only; unknown values are left blank. Missing scheduled delivery entries: {{ $missingDeliveryCount }}. Printing is available.</span>
            @elseif ($form === '13' && ! $ready)
                <span role="alert">Some Form 13 data is incomplete. The report is generated from currently available records.</span>
            @endif
        @else
            <span role="alert">Report information is incomplete. Preview leaves unknown values blank; finish the required school stock records before printing.</span>
        @endif
    </div>
    @foreach ($pages as $page)
        <div class="official-page">
            <img class="official-artwork" src="{{ asset($page['artwork']) }}" alt="" aria-hidden="true">
            @foreach ($page['overlays'] as $field)
                <div class="official-field" style="left:{{ $field['x'] / 993 * 100 }}%;top:{{ $field['y'] / 1404 * 100 }}%;width:{{ $field['width'] / 993 * 100 }}%;height:{{ $field['height'] / 1404 * 100 }}%;font-size:{{ $field['font_size'] / 993 * 100 }}cqw;text-align:{{ $field['align'] }};background-color:#fff!important;color:#111!important;">{{ $field['text'] }}</div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
