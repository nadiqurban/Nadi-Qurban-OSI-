@php
    /** @var array<string, string> $company */
    $company ??= app(\App\Support\Settings::class)->group('company');
@endphp
{{-- Document header: logo + company block (Tempahan & Pelanggan.dc.html receipt / waybill) --}}
<div style="display:flex; align-items:center; gap:14px;">
    <div style="width:52px; height:52px; border-radius:11px; overflow:hidden;">
        <img src="{{ \App\Support\PdfAssets::image('images/logo-128.png') }}" alt="" style="width:100%; height:100%; object-fit:cover; display:block;">
    </div>
    <div>
        <div style="font-size:18px; font-weight:800; color:#42481c; letter-spacing:.3px;">{{ mb_strtoupper($company['name'] ?? 'Nadi Qurban Sdn. Bhd.') }}</div>
        <div style="font-size:10.5px; font-weight:600; letter-spacing:2px; color:#94A3AC;">OSI SYSTEM</div>
        @if ($withAddress ?? true)
            <div style="font-size:11px; color:#64748B; line-height:1.5; margin-top:6px;">
                {{ $company['address'] ?? '' }}<br>
                Tel: {{ $company['phone'] ?? '' }} · {{ $company['website'] ?? '' }}
            </div>
        @endif
    </div>
</div>
