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
    <title>{{ $title ? $title.' · ' : '' }}Portal Ejen · Nadi Qurban</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
{{-- Portal Ejen (Portal Ejen.dc.html): own login + single-column portal, no staff sidebar. --}}
<body class="min-h-screen bg-bg text-ink antialiased">
    {{ $slot }}

    <x-app.toast />

    @auth
        <form x-data="idleLogout({{ (int) config('session.lifetime') }})" x-ref="form" method="POST" action="{{ route('logout') }}" class="hidden">
            @csrf
            <input type="hidden" name="idle" value="1">
        </form>
    @endauth

    @livewireScripts
</body>
</html>
