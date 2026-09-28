<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form 13 report information · {{ $month }}</title>
    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])
</head>
<body>
    <x-dashboard-shell active="Official reports">
        <section class="user-management">
            <div class="user-management__heading">
                <div>
                    <p class="dashboard-eyebrow">Official Form 13</p>
                    <h2>Upazila monthly stock statement</h2>
                    <p>Report values are loaded from saved school, delivery, and supplier records. Unknown values stay blank.</p>
                </div>
                <a class="demand-setup__link" href="{{ route('admin.reports.index', ['month' => $month]) }}">All forms</a>
            </div>

            @if ($errors->any())
                <div class="user-management__notice user-management__notice--error" role="alert">{{ $errors->first() }}</div>
            @endif
            @if (session('status'))
                <div class="user-management__notice" role="status">{{ session('status') }}</div>
            @endif

            <section class="editable-table-card demand-setup__card">
                <div class="school-detail-card__heading">
                    <div><p class="dashboard-eyebrow">Reporting period</p><h3>Form 13 · {{ $month }}</h3></div>
                </div>
                <div class="demand-setup__body">
                    <form method="GET" action="{{ route('admin.reports.stock.information', ['form' => 13]) }}" class="stock-info-form">
                        <label>Month<input type="month" name="month" value="{{ $month }}" required></label>
                        <button type="submit">Load month</button>
                    </form>

                    <dl class="form-thirteen-metadata">
                        <div><dt>Schools</dt><dd>{{ $schoolCount }}</dd></div>
                        <div><dt>District</dt><dd>{{ $period->district_name ?: 'Not recorded' }}</dd></div>
                        <div><dt>Upazila</dt><dd>{{ $period->upazila_name ?: 'Not recorded' }}</dd></div>
                        <div><dt>Supplier/contractor</dt><dd>{{ $period->supplier_name ?: 'Not recorded' }}</dd></div>
                    </dl>

                    @if ($missingMetadata !== [])
                        <p class="stock-info-note">Enter only the report-level details that are not available in saved records. School-level stock fields are not entered here.</p>
                        <form method="POST" action="{{ route('admin.reports.stock.information.update', ['form' => 13]) }}" class="stock-info-form">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="month" value="{{ $month }}">
                            @foreach ($missingMetadata as $field => $label)
                                <label>{{ $label }}<input name="{{ $field }}" value="{{ old($field) }}" required maxlength="255"></label>
                            @endforeach
                            <button type="submit">Save missing details</button>
                        </form>
                    @endif

                    <p class="stock-info-note">Opening stock, received quantity, and distribution are read from existing records. Missing facts remain blank in the official report; generation is not blocked.</p>
                    <a class="stock-info-preview" href="{{ route('admin.reports.stock.preview', ['form' => 13, 'month' => $month]) }}">Preview Form 13</a>
                </div>
            </section>
        </section>
    </x-dashboard-shell>
    <style>
        .stock-info-form{display:flex;flex-wrap:wrap;align-items:end;gap:1rem;margin:1rem 0}
        .stock-info-form label{display:grid;gap:.35rem;min-width:12rem;flex:1;color:var(--ui-muted)}
        .stock-info-form input{width:100%;padding:.6rem;border:1px solid var(--ui-line);border-radius:.55rem;background:var(--ui-surface);color:var(--ui-ink)}
        .stock-info-form button,.stock-info-preview{display:inline-block;padding:.7rem 1rem;border:0;border-radius:.6rem;background:var(--ui-accent);color:var(--ui-on-accent);text-decoration:none;cursor:pointer}
        .stock-info-note{color:var(--ui-muted);line-height:1.5}
        .form-thirteen-metadata{display:grid;grid-template-columns:repeat(auto-fit,minmax(12rem,1fr));gap:.8rem;margin:1.25rem 0}
        .form-thirteen-metadata div{padding:.8rem 1rem;border:1px solid var(--ui-line);border-radius:.65rem;background:var(--ui-surface)}
        .form-thirteen-metadata dt{color:var(--ui-muted);font-size:.82rem}
        .form-thirteen-metadata dd{margin:.35rem 0 0;color:var(--ui-ink)}
        @media(max-width:520px){.stock-info-form{align-items:stretch;flex-direction:column}.stock-info-form label{min-width:0;width:100%}.stock-info-form button,.stock-info-preview{width:100%;text-align:center}.form-thirteen-metadata{grid-template-columns:minmax(0,1fr)}}
    </style>
</body>
</html>
