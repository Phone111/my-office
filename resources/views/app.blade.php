<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @php
            // แปลงชื่อเพจให้ตรงกับ path จริงใน Vite manifest
            // 'Dashboard'           -> resources/js/Pages/Dashboard.vue
            // 'Attendance::CheckIn' -> Modules/Attendance/resources/js/Pages/CheckIn.vue
            $component = $page['component'];
            $pagePath = str_contains($component, '::')
                ? 'Modules/'.str_replace('::', '/resources/js/Pages/', $component).'.vue'
                : "resources/js/Pages/{$component}.vue";
        @endphp
        @vite(['resources/js/app.js', $pagePath])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
