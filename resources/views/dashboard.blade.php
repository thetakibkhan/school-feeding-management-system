<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-dashboard-shell>
        <section>
            <p>Welcome back, {{ auth()->user()->name }}.</p>
            <h2>School feeding operations at a glance</h2>
            <p>Use the navigation to manage users, setup, and delivery reporting.</p>
        </section>
    </x-dashboard-shell>
</body>
</html>
