<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IMEI Finder</title>
    {{-- <title inertia>{{ config('app.name', 'Laravel') }}</title> --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- <meta name="csrf-token" content="{{ csrf_token() }}"> --}}
</head>
<body>
    @inertia
</body>
</html>