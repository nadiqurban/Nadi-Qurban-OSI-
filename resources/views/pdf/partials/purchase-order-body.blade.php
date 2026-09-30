@php
    /** @var \App\Models\PurchaseOrder $po  with vendor */
    $cur = $po->currency;
    $fmt = fn (int $minor) => $cur.' '.number_format($minor / 100, $minor % 100 === 0 ? 0 : 2);
    $label = 'font-size:11px; font-weight:700; letter-spacing:.5px; color:#94A3AC; text-transform:uppercase; margin-bottom:7px;';
    $th = 'font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase; padding:11px 14px;';
@endphp
{{-- PURCHASE ORDER (Vendor.dc.html #po-receipt) — shared by the preview modal and the A4 PDF. --}}
<div style="display:flex; align-items:flex-start; justify-content:space-between; gap:20px; padding-bottom:24px; border-bottom:2px solid #42481c;">
    <div style="display:flex; align-items:center; gap:14px;">
        <div style="width:52px; height:52px; border-radius:11px; overflow:hidden;"><img src="{{ \App\Support\PdfAssets::image('images/logo-128.png') }}" alt="Nadi Qurban" style="width:100%; height:100%; object-fit:cover; display:block;"></div>
        <div><div style="font-size:18px; font-weight:800; color:#42481c; letter-spacing:.3px;">NADI QURBAN</div><div style="font-size:10.5px; font-weight:600; letter-spacing:2px; color:#94A3AC;">OSI SYSTEM</div></div>
    </div>
    <div style="text-align:right;"><div style="font-size:22px; font-weight:800; color:#1A1D21; letter-spacing:1px;">PURCHASE ORDER</div><div style="font-size:12px; color:#64748B; margin-top:6px;">Tarikh: {{ tarikh($po->created_at) }}</div><div style="font-size:13px; font-weight:700; color:#42481c; margin-top:2px;">{{ $po->po_no }}</div></div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:26px; margin-top:24px;">
    <div><div style="{{ $label }}">Billing Address</div><div style="font-size:13px; color:#334155; line-height:1.6; white-space:pre-line;">{{ $po->billing_address }}</div></div>
    <div><div style="{{ $label }}">Shipping Address</div><div style="font-size:13px; color:#334155; line-height:1.6; white-space:pre-line;">{{ $po->shipping_address }}</div></div>
</div>

<div style="margin-top:24px; padding:11px 14px; background:#F6F8F7; border:1px solid #E2E8F0; border-radius:9px; display:flex; align-items:center; gap:8px;"><span style="font-size:11px; font-weight:700; letter-spacing:.5px; color:#94A3AC; text-transform:uppercase;">Nama Supplier:</span><span style="font-size:13.5px; font-weight:700; color:#42481c;">{{ $po->vendor->supplier ?: $po->vendor->companyName() }}</span></div>

<table style="width:100%; border-collapse:collapse; margin-top:14px;">
    <thead>
        <tr style="background:#42481c; color:#fff;">
            <th style="{{ $th }} text-align:left;">Perkara</th>
            <th style="{{ $th }} text-align:center;">Kuantiti</th>
            <th style="{{ $th }} text-align:right;">Harga Seunit</th>
            <th style="{{ $th }} text-align:right;">Jumlah</th>
        </tr>
    </thead>
    <tbody>
        <tr style="border-bottom:1px solid #E2E8F0;">
            <td style="font-size:13px; color:#1A1D21; padding:13px 14px;">{{ $po->service->label() }} &mdash; {{ $po->animal_label }}</td>
            <td style="font-size:13px; color:#334155; text-align:center; padding:13px 14px;">{{ $po->quantity }}</td>
            <td style="font-size:13px; color:#334155; text-align:right; padding:13px 14px;">{{ $fmt($po->unit_price_minor) }}</td>
            <td style="font-size:13px; color:#1A1D21; text-align:right; padding:13px 14px; font-weight:600;">{{ $fmt($po->total_minor) }}</td>
        </tr>
    </tbody>
</table>

<div style="display:flex; justify-content:flex-end; margin-top:16px;">
    <div style="width:260px; display:flex; flex-direction:column; gap:8px;">
        <div style="display:flex; justify-content:space-between; font-size:13px; color:#64748B;"><span>Subjumlah</span><span>{{ $fmt($po->total_minor) }}</span></div>
        <div style="display:flex; justify-content:space-between; font-size:13px; color:#64748B;"><span>Cukai (0%)</span><span>{{ $fmt(0) }}</span></div>
        <div style="display:flex; justify-content:space-between; font-size:16px; font-weight:800; color:#42481c; border-top:2px solid #42481c; padding-top:9px;"><span>Jumlah Keseluruhan</span><span>{{ $fmt($po->total_minor) }}</span></div>
        @if ($cur !== 'RM')
            <div style="text-align:right; font-size:11px; color:#94A3AC;">≈ {{ rm($po->total_rm_sen) }} @ {{ rtrim(rtrim((string) $po->exchange_rate, '0'), '.') }}</div>
        @endif
    </div>
</div>

@if ($po->notes)
    <div style="margin-top:24px;"><div style="{{ $label }}">Notes</div><div style="font-size:13px; color:#334155; line-height:1.6; white-space:pre-line; background:#F6F8F7; border-radius:9px; padding:12px 14px;">{{ $po->notes }}</div></div>
@endif

<div style="margin-top:32px; padding-top:16px; border-top:1px solid #E2E8F0; text-align:center; font-size:11px; color:#94A3AC;">Dokumen ini dijana secara automatik oleh Nadi Qurban Sdn Bhd &middot; {{ $po->po_no }} &middot; Sah tanpa tandatangan basah.</div>
