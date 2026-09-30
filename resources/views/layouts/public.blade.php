@props([
    'title' => null,
    'subtitle' => 'SEMAK STATUS IBADAH',
    'maxWidth' => '820px',     // Tracking 820px · Portal Ansuran 520px
    'helpHref' => null,        // WhatsApp/help link from settings
])
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? $title.' · ' : '' }}Nadi Qurban</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-bg">
    <div class="flex min-h-screen flex-col bg-bg" style="--page-max: {{ $maxWidth }}">
        <header class="border-b border-border bg-surface">
            <div class="mx-auto flex max-w-(--page-max) items-center gap-[13px] px-4 py-4 md:px-6">
                <div class="size-10 overflow-hidden rounded-[10px]"><img src="{{ asset('images/logo-mark-128.png') }}" alt="Nadi Qurban" class="block size-full scale-[1.22] object-cover"></div>
                <div class="leading-none">
                    <div class="text-[15px] font-extrabold tracking-[.3px] text-primary">NADI QURBAN</div>
                    <div class="mt-[3px] text-[10px] font-semibold tracking-[2px] text-faint">{{ $subtitle }}</div>
                </div>
                <a href="{{ $helpHref ?? '#bantuan' }}" class="ml-auto flex min-h-11 items-center gap-1.5 text-[13px] font-semibold text-muted hover:text-primary">
                    <i class="ph ph-question text-[17px]"></i> Bantuan
                </a>
            </div>
        </header>

        <main class="mx-auto w-full max-w-(--page-max) flex-1 px-4 pt-6 pb-14 md:px-6 md:pt-8">
            {{ $slot }}
        </main>
    </div>
    @livewireScripts
</body>
</html>
