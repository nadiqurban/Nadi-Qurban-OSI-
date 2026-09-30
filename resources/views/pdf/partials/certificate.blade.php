@php
    /** @var array<string, string|null> $c  from CertificateTemplate::render() */
    $girih = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='44' height='44' viewBox='0 0 44 44'><g fill='none' stroke='%23C9A227' stroke-width='1.1' stroke-opacity='0.28'><path d='M22 0 L30 8 L22 16 L14 8 Z'/><path d='M0 22 L8 14 L16 22 L8 30 Z'/><path d='M44 22 L36 14 L28 22 L36 30 Z'/><path d='M22 44 L30 36 L22 28 L14 36 Z'/><path d='M22 16 L28 22 L22 28 L16 22 Z'/><path d='M8 8 L14 8 M8 8 L8 14 M36 8 L30 8 M36 8 L36 14 M8 36 L14 36 M8 36 L8 30 M36 36 L30 36 M36 36 L36 30'/></g></svg>";
    $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $c['color']) ? $c['color'] : '#42481c';
@endphp
{{-- A5 certificate at 472×669 design px (Tempahan & Pelanggan.dc.html sijil preview). --}}
<div style="width:472px; height:669px; box-sizing:border-box; font-family:Georgia,serif; color:#1A1D21; position:relative; background:#fff; overflow:hidden;">
    @if ($c['background'])
        <img src="{{ $c['background'] }}" alt="" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover;">
    @else
        <div style="position:absolute; inset:0; background-color:{{ $color }}; background-image:url(&quot;{{ $girih }}&quot;); background-size:22px 22px;"></div>
        <div style="position:absolute; inset:14px; background:#C9A227;"></div>
        <div style="position:absolute; inset:17px; background:{{ $color }};"></div>
        <div style="position:absolute; inset:22px; background:#fff;"></div>
        <div style="position:absolute; inset:28px; border:1px solid #C9A227;"></div>
        <div style="position:absolute; inset:30px; border:1.5px solid #C9A227;"></div>
    @endif
    <div style="position:absolute; inset:48px; display:flex; flex-direction:column;">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:52px; height:52px; border-radius:11px; overflow:hidden; flex-shrink:0;"><img src="{{ \App\Support\PdfAssets::image('images/logo-128.png') }}" alt="logo" style="width:100%; height:100%; object-fit:fill; display:block;"></div>
            <div style="flex:1; height:1px; background:#C9A227;"></div>
            <div style="text-align:right;">
                <div style="font-size:19px; letter-spacing:1px;"><span style="font-style:italic; color:#1A1D21;">Nadi</span> <span style="font-weight:bold; color:#C9A227;">QURBAN</span></div>
                <div style="font-size:10px; letter-spacing:3px; color:#42481c; font-family:Arial,sans-serif; margin-top:2px;">{{ $c['title'] }}</div>
            </div>
        </div>
        <div style="flex:1; text-align:center; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:4px;">
            <div style="font-size:15px; font-weight:bold; letter-spacing:2.5px; color:#42481c; margin-bottom:12px; font-family:Arial,sans-serif;">SIJIL PENYERTAAN</div>
            <div style="font-size:12.5px; color:#64748B; font-family:Arial,sans-serif;">{{ $c['intro'] }}</div>
            <div style="font-size:22px; font-weight:bold; color:#1A1D21; margin:8px 0; padding-bottom:12px; border-bottom:1px solid #C9A227; line-height:1.3;">{{ $c['name'] }}</div>
            <div style="font-size:12.5px; color:#64748B; font-family:Arial,sans-serif;">{{ $c['close'] }}</div>
            <div style="font-size:19px; font-weight:bold; color:#C9A227; font-family:Georgia,serif; letter-spacing:1px; margin-top:6px;">{{ $c['service'] }}</div>
            <div style="font-size:16px; font-weight:bold; color:#1A1D21; font-family:Arial,sans-serif; margin-top:2px;">{{ $c['detail'] }}</div>
            <div style="font-size:12.5px; color:#64748B; font-family:Arial,sans-serif; margin-top:14px;">bertempat di :</div>
            <div style="font-size:15px; font-weight:bold; color:#1A1D21; font-family:Arial,sans-serif; margin-top:3px;">{{ $c['country'] }}</div>
            <div style="font-size:15px; font-weight:bold; color:#1A1D21; font-family:Arial,sans-serif; margin-top:1px;">{{ $c['date'] }}</div>
            <div style="font-size:19px; color:#42481c; font-family:Georgia,serif; letter-spacing:3px; margin-top:20px;">{{ $c['jazak'] }}</div>
        </div>
        <div style="display:flex; align-items:center; gap:12px; margin-top:12px; padding-top:14px; border-top:1px solid #E2E8F0;">
            <div style="width:62px; height:62px; flex-shrink:0;">{!! \App\Support\PdfAssets::qr((string) $c['qr_url'], 62) !!}</div>
            <div style="font-family:Arial,sans-serif;"><div style="font-size:12px; color:#64748B;">Daftar Online ?</div><div style="font-size:13px; font-weight:bold; color:#1A1D21;">nadiqurban.com</div></div>
            <div style="margin-left:auto; text-align:right; font-family:Arial,sans-serif;">
                <div style="font-size:10px; color:#94A3AC;">No. Sijil</div><div style="font-size:12px; font-weight:bold; color:#42481c;">{{ $c['certificate_no'] }}</div>
                <div style="font-size:10px; color:#94A3AC; margin-top:4px;">No. Tracking</div><div style="font-size:12px; font-weight:bold; color:#1A1D21;">{{ $c['tracking_no'] }}</div>
            </div>
        </div>
        <div style="text-align:center; font-size:10px; color:#94A3AC; font-family:Arial,sans-serif; margin-top:10px;">Hakcipta Terpelihara. {{ app(\App\Support\Settings::class)->get('company.name', 'Nadi Qurban Sdn Bhd') }} ({{ app(\App\Support\Settings::class)->get('company.ssm', '1677511-A') }})</div>
    </div>
</div>
