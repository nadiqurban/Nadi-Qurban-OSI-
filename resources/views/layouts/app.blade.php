@props(['title' => null])
<!DOCTYPE html>
<html lang="ms" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}Nadi Qurban OSI</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <script>
        try { if (localStorage.getItem('nq_theme') === 'dark') document.documentElement.dataset.theme = 'dark'; } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-bg text-ink">
    <div class="flex min-h-screen bg-bg">
        <x-app.sidebar />

        <div class="flex min-w-0 flex-1 flex-col">
            <x-app.header />

            <main class="flex-1 px-4 pt-5 pb-14 md:px-6 md:pt-6 lg:px-8 lg:pt-7">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-app.search-overlay />

    @livewireScripts
</body>
</html>
