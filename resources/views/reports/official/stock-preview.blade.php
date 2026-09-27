<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form {{ $form }} · {{ $month }}</title>
    <link rel="stylesheet" href="{{ asset('stock-forms/stock-forms.css') }}">
</head>
<body>
    <div class="report-toolbar">
        <strong>Official Form {{ $form }} · {{ $month }}</strong>
        <a href="{{ route('admin.reports.stock.information', ['form' => $form, 'month' => $month]) }}">Report information</a>
        @if ($ready)
            <button type="button" onclick="window.print()">Print</button>
            <a href="{{ route('admin.reports.stock.pdf', ['form' => $form, 'month' => $month]) }}">Download PDF</a>
        @else
            <span role="alert">Report information is incomplete. Preview leaves unknown values blank; finish the required school stock records before printing or downloading.</span>
        @endif
    </div>
    @foreach ($pages as $page)
        <div class="official-page">
            <img class="official-artwork" src="{{ asset($page['artwork']) }}" alt="" aria-hidden="true">
            @foreach ($page['overlays'] as $field)
                <div class="official-field" style="left:{{ $field['x'] / 993 * 100 }}%;top:{{ $field['y'] / 1404 * 100 }}%;width:{{ $field['width'] / 993 * 100 }}%;height:{{ $field['height'] / 1404 * 100 }}%;font-size:{{ $field['font_size'] / 993 * 210 }}mm;text-align:{{ $field['align'] }}">{{ $field['text'] }}</div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
