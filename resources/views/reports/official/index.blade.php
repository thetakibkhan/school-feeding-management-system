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
            @if (session('status'))
                <div class="user-management__notice" role="status">{{ session('status') }}</div>
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
                    <form method="POST" action="{{ route('admin.reports.period.update') }}" class="demand-setup__filter" style="padding:16px 0">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="month" value="{{ $month }}">
                        <label for="supplier-name">Supplier/contractor for {{ $month }}</label>
                        <input id="supplier-name" name="supplier_name" type="text" value="{{ old('supplier_name', $period->supplier_name) }}" maxlength="255" required>
                        <label for="invoice-number">Invoice number</label>
                        <input id="invoice-number" name="invoice_number" type="text" value="{{ old('invoice_number', $period->invoice_number) }}" maxlength="100">
                        <label for="invoice-date">Invoice date</label>
                        <input id="invoice-date" name="invoice_date" type="date" value="{{ old('invoice_date', $period->invoice_date?->format('Y-m-d')) }}">
                        <label for="contract-number">Contract number</label>
                        <input id="contract-number" name="contract_number" type="text" value="{{ old('contract_number', $period->contract_number) }}" maxlength="150">
                        <label for="bank-account-name">Bank account name</label>
                        <input id="bank-account-name" name="bank_account_name" type="text" value="{{ old('bank_account_name', $period->bank_account_name) }}" maxlength="255">
                        <label for="bank-account-number">Bank account number</label>
                        <input id="bank-account-number" name="bank_account_number" type="text" value="{{ old('bank_account_number', $period->bank_account_number) }}" maxlength="100">
                        <label for="bank-name">Bank name</label>
                        <input id="bank-name" name="bank_name" type="text" value="{{ old('bank_name', $period->bank_name) }}" maxlength="255">
                        <label for="bank-branch">Bank branch</label>
                        <input id="bank-branch" name="bank_branch" type="text" value="{{ old('bank_branch', $period->bank_branch) }}" maxlength="255">
                        <label for="bank-routing-number">Bank routing number</label>
                        <input id="bank-routing-number" name="bank_routing_number" type="text" value="{{ old('bank_routing_number', $period->bank_routing_number) }}" maxlength="100">
                        <button type="submit">Save period details</button>
                    </form>
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
