@props([
    'title' => null,
    'stats' => null,   // [['value' => '7', 'label' => 'Negara Pelaksanaan'], ...]; defaults to App\Support\BrandStats
])
@php($stats ??= app(\App\Support\BrandStats::class)->all())
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}Nadi Qurban OSI</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-bg">
    <div class="flex min-h-screen bg-bg">
        {{-- Brand panel (Login.dc.html): 44% / max 620px; hidden on phones --}}
        <div class="relative hidden w-[40%] max-w-[620px] flex-col overflow-hidden bg-primary px-10 py-12 text-white md:flex lg:w-[44%] lg:px-14">
            <div class="relative flex items-center gap-[14px]">
                <div class="size-[46px] overflow-hidden rounded-[12px]">
                    <img src="{{ asset('images/logo-mark-128.png') }}" alt="Nadi Qurban" class="block size-full scale-[1.22] object-cover">
                </div>
                <div class="leading-none">
                    <div class="text-[18px] font-extrabold tracking-[.3px]">NADI QURBAN</div>
                    <div class="mt-1 text-[11px] font-semibold tracking-[3px] text-gold">OSI SYSTEM</div>
                </div>
            </div>

            <div class="relative mt-auto">
                <h1 class="text-[26px] leading-[1.2] font-extrabold tracking-[-.5px] lg:text-[32px]">Sistem Operasi<br>Ibadah Qurban &amp; Aqiqah</h1>
                <p class="mt-4 max-w-[440px] text-[15px] leading-[1.6] text-[#cfe0d7]">Uruskan tempahan, vendor, pelaksanaan dan pensijilan Qurban, Aqiqah, Dam &amp; Nazar Haiwan merentas {{ $stats[0]['value'] ?? '7' }} negara &mdash; dalam satu platform yang selamat.</p>
                <div class="mt-[22px] flex flex-col gap-[11px]">
                    @foreach (['Jejak status pelaksanaan secara langsung', 'Pensijilan & laporan digital automatik', 'Pengurusan vendor & kewangan bersepadu'] as $feature)
                        <div class="flex items-center gap-[10px] text-[13.5px] text-[#e6efe9]"><i class="ph-fill ph-check-circle text-[18px] text-gold"></i> {{ $feature }}</div>
                    @endforeach
                </div>
                @if ($stats)
                    <div class="mt-[30px] flex gap-3">
                        @foreach ($stats as $stat)
                            <div class="flex-1 rounded-[11px] border border-white/10 bg-white/6 px-4 py-[14px]">
                                <div class="text-[22px] font-extrabold text-gold">{{ $stat['value'] }}</div>
                                <div class="mt-[3px] text-[12px] text-[#cfe0d7]">{{ $stat['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="relative mt-[38px] text-[12px] text-white">&copy; {{ app(\App\Support\Settings::class)->get('season.year', now()->year) }} Nadi Qurban Sdn. Bhd. Hak cipta terpelihara.</div>
        </div>

        {{-- Form panel --}}
        <div class="flex flex-1 items-center justify-center px-5 py-10 md:px-6">
            <div class="w-full max-w-[400px]">
                <div class="mb-8 flex items-center gap-3 md:hidden">
                    <div class="size-11 overflow-hidden rounded-[12px]"><img src="{{ asset('images/logo-mark-128.png') }}" alt="" class="size-full scale-[1.22] object-cover"></div>
                    <div class="leading-none">
                        <div class="text-[17px] font-extrabold tracking-[.3px] text-primary">NADI QURBAN</div>
                        <div class="mt-1 text-[10.5px] font-semibold tracking-[2px] text-faint">OSI SYSTEM</div>
                    </div>
                </div>
                {{ $slot }}
            </div>
        </div>
    </div>
    @livewireScripts
</body>
</html>
