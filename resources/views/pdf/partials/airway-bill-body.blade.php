@php
    /** @var \App\Models\Shipment $shipment  with order.customer, order.country */
    $company ??= app(\App\Support\Settings::class)->group('company');
    $order = $shipment->order;
    $c = $order->customer;
@endphp
{{-- AIRWAY BILL (AWB & Postage.dc.html preview) — shared by the in-app preview and the PDF. --}}
<div style="display:flex; align-items:flex-start; justify-content:space-between; gap:20px; padding-bottom:20px; border-bottom:2px solid #42481c;">
    @include('pdf.partials.company-header', ['company' => $company, 'withAddress' => false])
    <div style="text-align:right;">
        <div style="font-size:20px; font-weight:800; letter-spacing:1px; color:#1A1D21;">AIRWAY BILL</div>
        <div style="font-size:13px; font-weight:700; color:#42481c; margin-top:4px;">{{ $shipment->consignment_no }}</div>
        <div style="font-size:12px; color:#64748B; margin-top:2px;">{{ $shipment->generated_at->format('d-m-Y') }}</div>
    </div>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:22px; margin-top:22px;">
    <div>
        <div style="font-size:11px; font-weight:700; letter-spacing:.5px; color:#94A3AC; text-transform:uppercase; margin-bottom:8px;">Penghantar</div>
        <div style="font-size:12.5px; color:#334155; line-height:1.6;">{{ mb_strtoupper($company['name'] ?? '') }}<br>{{ $company['address'] ?? '' }}<br>Tel: {{ $company['phone'] ?? '' }}</div>
    </div>
    <div>
        <div style="font-size:11px; font-weight:700; letter-spacing:.5px; color:#94A3AC; text-transform:uppercase; margin-bottom:8px;">Penerima</div>
        <div style="font-size:12.5px; color:#334155; line-height:1.6;">
            <span style="font-weight:700; color:#1A1D21;">{{ $shipment->recipient_name }}</span><br>
            {{ $shipment->address }}<br>
            Poskod: {{ $shipment->postcode ?: '-' }} · {{ collect([$shipment->city, $shipment->state])->filter()->implode(', ') ?: '-' }}<br>
            Tel: {{ $shipment->phone ?: '-' }}<br>
            Emel: {{ $c->email ?: '-' }}
        </div>
    </div>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-top:24px;">
    <div style="background:#F6F8F7; border-radius:10px; padding:12px 14px;"><div style="font-size:11px; color:#94A3AC;">Kurier</div><div style="font-size:13px; font-weight:700; margin-top:3px; color:#42481c;">{{ $shipment->courier->label() }}</div></div>
    <div style="background:#F6F8F7; border-radius:10px; padding:12px 14px;"><div style="font-size:11px; color:#94A3AC;">Jenis Pos</div><div style="font-size:13px; font-weight:700; margin-top:3px;">{{ $shipment->post_type->label() }}</div></div>
    <div style="background:#F6F8F7; border-radius:10px; padding:12px 14px;"><div style="font-size:11px; color:#94A3AC;">Kandungan</div><div style="font-size:13px; font-weight:700; margin-top:3px;">Sijil {{ $order->service->label() }}</div></div>
</div>
<div style="margin-top:24px; border:1px solid #E2E8F0; border-radius:10px; padding:16px; display:flex; align-items:center; gap:16px;">
    <div style="width:88px; height:88px; flex-shrink:0;">{!! \App\Support\PdfAssets::qr($order->trackingUrl(), 88) !!}</div>
    <div>
        <div style="font-size:11px; color:#94A3AC;">Imbas untuk jejak penghantaran</div>
        <div style="font-size:16px; font-weight:800; color:#1A1D21; letter-spacing:1px; margin-top:4px;">{{ $shipment->consignment_no }}</div>
        <div style="font-size:12px; color:#64748B; margin-top:4px;">{{ $order->order_no }} · {{ $order->tracking_no }}</div>
    </div>
</div>
<div style="margin-top:28px; padding-top:14px; border-top:1px solid #E2E8F0; text-align:center; font-size:11px; color:#94A3AC;">Dijana oleh Nadi Qurban OSI · {{ $shipment->consignment_no }} · Sah tanpa tandatangan basah.</div>
