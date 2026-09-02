<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="WeatherLens weather dashboard">
        <link rel="icon" type="image/png" href="{{ asset('images/weatherlens-icon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/weatherlens-icon.png') }}">

        @inertiaHead
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
