<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correct delivery</title>
    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])
</head>
<body>
    <x-dashboard-shell active="My entries">
        <section class="user-management delivery-management">
            <div class="user-management__heading">
                <div><p class="dashboard-eyebrow">Daily delivery · {{ $delivery->date->format('d M Y') }}</p><h2>Correct entry</h2><p class="font-bangla">{{ $delivery->school->name }}</p></div>
                <a class="delivery-back-link" href="{{ route('field-staff.deliveries.index') }}">Back to my entries</a>
            </div>

            @if ($errors->any())
                <div class="user-management__notice user-management__notice--error" role="alert">{{ $errors->first() }}</div>
            @endif

            <section class="editable-table-card delivery-form-card">
                <div class="school-detail-card__heading"><div><p class="dashboard-eyebrow">Correction</p><h3>Update delivered quantities</h3><p>Each saved correction keeps the previous quantities and chalan reference.</p></div></div>
                <div class="delivery-current-photo"><span>Current chalan</span><a href="{{ route('field-staff.deliveries.chalan', $delivery) }}" data-photo-open>View photo</a></div>
                <form class="delivery-form" method="POST" action="{{ route('field-staff.deliveries.update', $delivery) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="delivery-quantity-row"><label for="bun_quantity">Bun <small>(packets)</small></label><input id="bun_quantity" type="number" name="bun_quantity" min="0" step="1" value="{{ old('bun_quantity', $delivery->bun_quantity) }}" required></div>
                    <div class="delivery-quantity-row"><label for="egg_quantity">Boiled Egg <small>(pieces)</small></label><input id="egg_quantity" type="number" name="egg_quantity" min="0" step="1" value="{{ old('egg_quantity', $delivery->egg_quantity) }}" required></div>
                    <div class="delivery-quantity-row"><label for="banana_quantity">Banana <small>(pieces)</small></label><input id="banana_quantity" type="number" name="banana_quantity" min="0" step="1" value="{{ old('banana_quantity', $delivery->banana_quantity) }}" required></div>
                    <label for="chalan-number">Chalan number</label>
                    <input id="chalan-number" type="text" name="chalan_number" value="{{ old('chalan_number', $delivery->chalan_number) }}" maxlength="100" required>
                    <label for="chalan-date">Chalan date</label>
                    <input id="chalan-date" type="date" name="chalan_date" value="{{ old('chalan_date', $delivery->chalan_date?->toDateString() ?? $delivery->date->toDateString()) }}" required>
                    <label for="chalan-photo">Replace chalan photo <span class="delivery-optional">Optional</span></label>
                    <input id="chalan-photo" type="file" name="chalan_photo" accept="image/jpeg,image/png,image/webp">
                    <p class="delivery-help">Leave empty to keep the current photo. JPG, PNG, or WebP · maximum 5 MB.</p>
                    <button class="user-management__primary-action" type="submit">Save correction</button>
                </form>
            </section>
        </section>
        @include('field-staff.deliveries._photo-modal')
    </x-dashboard-shell>
</body>
</html>
