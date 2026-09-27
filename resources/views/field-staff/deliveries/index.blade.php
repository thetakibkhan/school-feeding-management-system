<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My delivery entries</title>
    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])
</head>
<body>
    <x-dashboard-shell active="My entries">
        <section class="user-management delivery-management">
            <div class="user-management__heading">
                <div>
                    <p class="dashboard-eyebrow">Daily delivery</p>
                    <h2>My entries</h2>
                    <p>Review and correct the delivery entries you submitted.</p>
                </div>
                <a class="user-management__primary-action" href="{{ route('field-staff.deliveries.create') }}">+ Add delivery</a>
            </div>

            @if (session('status'))
                <div class="user-management__notice" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="user-management__notice user-management__notice--error" role="alert">{{ $errors->first() }}</div>
            @endif

            <div class="editable-table-card">
                <div class="editable-table-toolbar">
                    <span class="editable-table-count">{{ $deliveries->total() }} entries</span>
                </div>
                <div class="editable-table-scroll">
                    <table class="editable-table delivery-table">
                        <thead><tr><th>Date</th><th>School</th><th>Bun (packets)</th><th>Boiled Egg (pieces)</th><th>Banana (pieces)</th><th>Chalan</th><th>Action</th></tr></thead>
                        <tbody>
                            @forelse ($deliveries as $delivery)
                                <tr>
                                    <td>{{ $delivery->date->format('d M Y') }}</td>
                                    <td><strong class="font-bangla">{{ $delivery->school->name }}</strong></td>
                                    <td>{{ number_format($delivery->bun_quantity) }}</td>
                                    <td>{{ number_format($delivery->egg_quantity) }}</td>
                                    <td>{{ number_format($delivery->banana_quantity) }}</td>
                                    <td><a href="{{ route('field-staff.deliveries.chalan', $delivery) }}" data-photo-open>View photo</a></td>
                                    <td><a href="{{ route('field-staff.deliveries.edit', $delivery) }}">Correct</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="editable-table-empty">No delivery entries yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="school-pagination">{{ $deliveries->onEachSide(1)->links() }}</div>
            </div>
        </section>
        @include('field-staff.deliveries._photo-modal')
    </x-dashboard-shell>
</body>
</html>
