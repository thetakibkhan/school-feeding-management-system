<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Form 4 — {{ $report['month'] }}</title>
    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('form-4/form-four.css') }}?v={{ filemtime(public_path('form-4/form-four.css')) }}">
</head>
<body class="form-four-screen">
<x-app-shell>
    <main class="form-four-shell">
        <header class="form-four-controls">
            <div>
                <a href="{{ route('admin.reports.index', ['month' => $report['month']]) }}" class="form-seven-back">← Official reports</a>
                <h1>Official Form 4</h1>
                <p>{{ $report['school_count'] }} school pages · {{ $report['month_label'] }} {{ $report['month'] }}</p>
            </div>
            <form method="GET" action="{{ route('admin.reports.form-four') }}">
                <label for="form-four-month">Month</label>
                <input id="form-four-month" name="month" type="month" value="{{ $report['month'] }}" required>
                <button type="submit">Show</button>
                <button type="button" onclick="window.print()">Print all</button>
            </form>
        </header>

        @if ($report['warnings'] !== [])
            <section class="form-four-warning" role="status">
                <h2>Application notes</h2>
                <ul>
                    @foreach ($report['warnings'] as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @forelse ($pages as $page)
            <section class="form-four-paper" aria-label="Form 4 for {{ $page['school']->name }}">
                <img class="form-four-artwork" src="{{ asset('form-4/page-1.png') }}" alt="" aria-hidden="true">
                @foreach ($page['overlays'] as $overlay)
                    <div @class(['form-four-overlay', 'font-bangla', 'form-four-overlay--bordered' => $overlay['border']])
                        style="left:{{ $overlay['x'] / 993 * 100 }}%;top:{{ $overlay['y'] / 1404 * 100 }}%;width:{{ $overlay['width'] / 993 * 100 }}%;height:{{ $overlay['height'] / 1404 * 100 }}%;font-size:{{ $overlay['font_size'] * 100 / 993 }}cqw;font-weight:{{ $overlay['bold'] ? '700' : '400' }};text-align:{{ $overlay['align'] }};">
                        {!! nl2br(e($overlay['text'])) !!}
                    </div>
                @endforeach
            </section>
        @empty
            <p class="form-four-warning">No schools are available for this report.</p>
        @endforelse
    </main>
</x-app-shell>
</body>
</html>
