<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Tiada Akses · Nadi Qurban</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-bg px-4">
    <main class="w-full max-w-[420px] rounded-[14px] border border-border bg-surface px-6 py-8 text-center">
        <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-danger-soft text-danger">
            <i class="ph-fill ph-lock-key text-[28px]"></i>
        </div>
        <h1 class="text-[20px] font-extrabold text-ink">Tiada Akses</h1>
        <p class="mt-2 text-[13.5px] leading-[1.6] text-muted">Anda tidak mempunyai kebenaran untuk membuka halaman ini. Sila hubungi Super Admin jika anda memerlukan akses.</p>
        <div class="mt-6 flex flex-col gap-2">
            @auth
                <a href="{{ route('home') }}" class="flex min-h-11 items-center justify-center gap-2 rounded-[9px] bg-primary px-4 text-[13.5px] font-semibold text-white hover:bg-primary-hover">
                    <i class="ph ph-house text-[16px]"></i> Ke halaman utama saya
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex min-h-11 w-full items-center justify-center gap-2 rounded-[9px] border border-border bg-surface px-4 text-[13.5px] font-semibold text-ink-2">
                        <i class="ph ph-sign-out text-[16px]"></i> Log keluar
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="flex min-h-11 items-center justify-center rounded-[9px] bg-primary px-4 text-[13.5px] font-semibold text-white">Log Masuk</a>
            @endauth
        </div>
    </main>
</body>
</html>
