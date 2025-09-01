<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        


        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

       <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx'])
        @inertiaHead

        {{-- Google Maps (load only when enabled + key present) --}}
        @if (!config('app.maps_disabled') && config('services.google.maps_browser_key'))
            <script
                src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_browser_key') }}"
                defer
            ></script>
        @endif


        
        <script
        
          src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_browser_key') }}"
          defer
        ></script>

    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
