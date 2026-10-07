@props([
    'title' => null,
])
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Tempah ibadah Qurban, Aqiqah, Nazar & Dam bersama Nadi Qurban Sdn. Bhd. — untuk fakir Muslim di lebih 15 buah negara.">
    <title>{{ $title ? $title.' · ' : '' }}Nadi Qurban</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
{{-- Tempahan Awam (Tempahan Awam.dc.html): public booking + receipt. --}}
<body class="min-h-screen bg-bg text-ink antialiased">
    {{ $slot }}
    <x-app.toast />
    @livewireScripts
</body>
</html>
