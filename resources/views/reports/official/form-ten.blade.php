<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Form 10 — {{ $form['month_label'] }}</title>
    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('form-10/form-ten.css') }}?v={{ filemtime(public_path('form-10/form-ten.css')) }}">
</head>
<body class="form-ten-screen">
<x-app-shell>
    <main class="form-ten-shell">
        <header class="form-ten-controls">
            <div>
                <a href="{{ route('admin.reports.index', ['month' => $form['month']]) }}" class="form-seven-back">← Official reports</a>
                <h1>Official Form 10</h1>
                <p>September supplier: {{ $form['supplier_name'] ?: 'Not recorded' }}</p>
            </div>
            <form method="GET" action="{{ route('admin.reports.form-ten') }}">
                <label for="form-ten-month">Month</label>
                <input id="form-ten-month" name="month" type="month" value="{{ $form['month'] }}" required>
                <button type="submit">Show</button>
                <button type="button" onclick="window.print()">Print</button>
                <a href="{{ route('admin.reports.form-ten.pdf', ['month' => $form['month']]) }}">Download PDF</a>
            </form>
        </header>

        @if (session('status'))
            <div class="form-ten-notice" role="status">{{ session('status') }}</div>
        @endif

        <section class="form-ten-metadata" aria-labelledby="form-ten-metadata-heading">
            <h2 id="form-ten-metadata-heading">Form 10 report information</h2>
            <p>These details are optional. You can still preview or print the report without them.</p>
            <form method="POST" action="{{ route('admin.reports.period.update') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="month" value="{{ $form['month'] }}">
                <div class="form-ten-metadata-grid">
                    <label for="supplier-name">Supplier/contractor
                        <input id="supplier-name" name="supplier_name" type="text" value="{{ old('supplier_name', $form['period']->supplier_name) }}" maxlength="255">
                    </label>
                    <label for="invoice-date">Invoice date
                        <input id="invoice-date" name="invoice_date" type="date" value="{{ old('invoice_date', $form['period']->invoice_date?->format('Y-m-d')) }}">
                    </label>
                    <label for="contract-number">Contract number
                        <input id="contract-number" name="contract_number" type="text" value="{{ old('contract_number', $form['period']->contract_number) }}" maxlength="150">
                    </label>
                    <label for="bank-account-name">Bank account name
                        <input id="bank-account-name" name="bank_account_name" type="text" value="{{ old('bank_account_name', $form['period']->bank_account_name) }}" maxlength="255">
                    </label>
                    <label for="bank-account-number">Bank account number
                        <input id="bank-account-number" name="bank_account_number" type="text" value="{{ old('bank_account_number', $form['period']->bank_account_number) }}" maxlength="100">
                    </label>
                    <label for="bank-name">Bank name
                        <input id="bank-name" name="bank_name" type="text" value="{{ old('bank_name', $form['period']->bank_name) }}" maxlength="255">
                    </label>
                    <label for="bank-branch">Bank branch
                        <input id="bank-branch" name="bank_branch" type="text" value="{{ old('bank_branch', $form['period']->bank_branch) }}" maxlength="255">
                    </label>
                    <label for="bank-routing-number">Bank routing number
                        <input id="bank-routing-number" name="bank_routing_number" type="text" value="{{ old('bank_routing_number', $form['period']->bank_routing_number) }}" maxlength="100">
                    </label>
                </div>
                <button type="submit">Save Form 10 information</button>
            </form>
        </section>

        @if ($form['warnings'] !== [])
            <section class="form-ten-warning" role="status">
                <h2>Some report values are missing</h2>
                <ul>
                    @foreach ($form['warnings'] as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
                <p>Missing delivery rows contribute zero to the current aggregation; they are not stored or counted as challans.</p>
            </section>
        @endif

        <section class="form-ten-paper" aria-label="Form 10 preview">
            <img class="form-ten-artwork" src="{{ asset('form-10/page-1.png') }}" alt="" aria-hidden="true">
            @foreach ($overlays as $overlay)
                <div class="form-ten-overlay {{ $overlay['font_family'] === 'latin' ? 'form-ten-overlay--latin' : '' }} {{ str_contains($overlay['text'], 'উপযুক্ত বিবরণ') || str_contains($overlay['text'], 'এমতাবস্থায়') ? 'form-ten-overlay--paragraph' : '' }}"
                    style="left:{{ $overlay['x'] / 993 * 100 }}%;top:{{ $overlay['y'] / 1404 * 100 }}%;width:{{ $overlay['width'] / 993 * 100 }}%;height:{{ $overlay['height'] / 1404 * 100 }}%;font-size:{{ $overlay['font_size'] * 100 / 993 }}cqw;line-height:{{ $overlay['line_height'] }};font-weight:{{ $overlay['bold'] ? '700' : '400' }};text-align:{{ $overlay['align'] }};justify-content:{{ $overlay['align'] === 'left' ? 'flex-start' : ($overlay['align'] === 'right' ? 'flex-end' : 'center') }};">
                    {!! nl2br(e($overlay['text'])) !!}
                </div>
            @endforeach
        </section>
    </main>
</x-app-shell>
</body>
</html>
