<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff account created</title>
    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])
</head>
<body>
    <x-app-shell>
        <main class="user-created-page">
            <section class="user-created-card" aria-labelledby="user-created-title">
                <p class="dashboard-eyebrow">Staff account</p>
                <h1 id="user-created-title">{{ $staffName }} was added</h1>
                <p>The WhatsApp message is ready with the new username and password. It will not be sent until you review it and press Send in WhatsApp.</p>
                <div class="user-created-actions">
                    <a class="user-management__primary-action" href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer">Open WhatsApp message</a>
                    <a class="user-created-back" href="{{ route('admin.users.index') }}">Back to users</a>
                </div>
            </section>
        </main>
    </x-app-shell>
</body>
</html>
