@php
    /** @var list<array<string, mixed>> $groups */
    /** @var string $date  "18-06-2027" */
    /** @var string $tag */
    $company = app(\App\Support\Settings::class)->group('company');
@endphp
<x-pdf.layout title="Senarai Peserta">
    <div class="page">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:20px; padding-bottom:22px; border-bottom:2px solid #42481c;">
            <div style="display:flex; align-items:center; gap:16px;">
                <div style="width:88px; height:88px; border-radius:16px; overflow:hidden; flex-shrink:0;">
                    <img src="{{ \App\Support\PdfAssets::image('images/logo-mark-512.png') }}" alt="" style="width:100%; height:100%; object-fit:cover; display:block; transform:scale(1.22);">
                </div>
                <div>
                    <div style="font-size:26px; font-weight:800; letter-spacing:1px; color:#42481c;">NADI QURBAN</div>
                    <div style="font-size:11px; font-weight:600; letter-spacing:2px; color:#94A3AC; margin-top:4px;">SENARAI PESERTA IBADAH</div>
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:16px; font-weight:700; color:#1A1D21;">{{ $date }}</div>
                <div style="font-size:16px; font-weight:700; color:#42481c; margin-top:6px;">{{ $tag }}</div>
            </div>
        </div>

        <div style="margin-top:14px; display:flex; flex-direction:column; gap:14px;">
            @foreach ($groups as $g)
                <div style="border:1px solid #E2E8F0; border-radius:10px; overflow:hidden; page-break-inside:avoid;">
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 14px; background:#F6F8F7; border-bottom:1px solid #E2E8F0;">
                        <span style="font-size:35px; font-weight:700; color:#42481c;">{{ $g['title'] }} · {{ $g['country'] }}</span>
                        <span style="font-size:11px; color:#64748B;">{{ $g['meta'] }}</span>
                    </div>
                    <div style="padding:14px 18px; display:flex; flex-direction:column; gap:8px;">
                        @foreach ($g['names'] as $i => $name)
                            <div style="font-size:35px; font-weight:700; color:#1A1D21;">{{ $i + 1 }}. {{ $name }}</div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top:34px; padding-top:16px; border-top:2px solid #42481c; text-align:center;">
            <div style="font-size:14px; font-weight:800; color:#42481c; letter-spacing:.5px;">{{ $company['website'] ?? 'www.nadiqurban.com' }}</div>
            <div style="font-size:11px; color:#94A3AC; margin-top:6px;">Dijana oleh Nadi Qurban OSI · Lembu/Unta 7 nama sekumpulan · Kambing 1 nama sekumpulan</div>
        </div>
    </div>
</x-pdf.layout>
