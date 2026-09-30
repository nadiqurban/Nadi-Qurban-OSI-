@props([
    'title' => null,
])
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>{{ $title ? $title.' · ' : '' }}Nadi Qurban</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
{{-- Portal Bayaran Ansuran (Portal Ansuran.dc.html): mobile-first, 520px column on #EEF1EC. --}}
<body class="min-h-screen bg-[#EEF1EC]">
    <div class="flex min-h-screen flex-col items-center px-4 pt-6 pb-12">
        {{ $slot }}
    </div>
    <x-app.toast />
    @livewireScripts
</body>
</html>
