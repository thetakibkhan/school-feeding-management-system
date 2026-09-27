<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Edit user</title>@vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])</head>
<body>
<x-app-shell>
    <main style="max-width: 560px; margin: 2rem auto; padding: 0 1rem;">
        <h1>Edit user</h1>
        @include('admin.users.form', ['action' => route('admin.users.update', $user), 'method' => 'PUT', 'user' => $user, 'passwordRequired' => false])
    </main>
</x-app-shell>
</body>
</html>
