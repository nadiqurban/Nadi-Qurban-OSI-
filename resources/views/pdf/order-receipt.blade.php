@php
    /** @var \App\Models\Order $order */
    $company = app(\App\Support\Settings::class)->group('company');
    $names = $order->participantNames();
@endphp
<x-pdf.layout :title="'Resit '.$order->order_no">
    <div class="page">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:20px; padding-bottom:24px; border-bottom:2px solid #42481c;">
            @include('pdf.partials.company-header', ['company' => $company])
            <div style="text-align:right;">
                <div style="font-size:20px; font-weight:800; color:#1A1D21; letter-spacing:1px;">RESIT TEMPAHAN</div>
                <div style="font-size:13px; font-weight:700; color:#42481c; margin-top:4px;">{{ $order->order_no }}</div>
                <div style="font-size:12px; color:#64748B; margin-top:2px;">Tracking: {{ $order->tracking_no }}</div>
                <div style="font-size:12px; color:#64748B; margin-top:2px;">Tarikh: {{ tarikh($order->created_at) }}</div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:22px; margin-top:24px;">
            @foreach (['Maklumat Pelanggan' => \App\Support\OrderPresenter::customerInfo($order), 'Butiran Tempahan' => \App\Support\OrderPresenter::orderInfo($order)] as $heading => $rows)
                <div>
                    <div class="label" style="margin-bottom:8px;">{{ $heading }}</div>
                    @foreach ($rows as $d)
                        <div style="display:flex; justify-content:space-between; gap:12px; font-size:12.5px; padding:3px 0;">
                            <span style="color:#64748B;">{{ $d['k'] }}</span>
                            <span style="color:#1A1D21; font-weight:600; text-align:right;">{{ $d['v'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div style="margin-top:26px; padding:16px 18px; background:#F6F8F7; border-radius:10px;">
            <div style="display:flex; justify-content:space-between; font-size:12.5px; color:#64748B; padding:2px 0;"><span>Harga ({{ $order->quantity }} × {{ rm($order->unit_price_sen) }})</span><span>{{ rm($order->subtotal_sen) }}</span></div>
            @if ($order->discount_sen > 0)
                <div style="display:flex; justify-content:space-between; font-size:12.5px; color:#64748B; padding:2px 0;"><span>Diskaun Promosi ({{ $order->promo_code }})</span><span>- {{ rm($order->discount_sen) }}</span></div>
            @endif
            <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #E2E8F0; margin-top:8px; padding-top:10px;">
                <span style="font-size:13px; font-weight:600; color:#334155;">Jumlah Keseluruhan</span>
                <span style="font-size:17px; font-weight:800; color:#42481c;">{{ rm($order->total_sen) }}</span>
            </div>
            <div style="display:flex; justify-content:space-between; font-size:12.5px; padding-top:8px;"><span style="color:#64748B;">Status Tempahan</span><span style="font-weight:800; color:#42481c;">{{ $order->status->label() }}</span></div>
        </div>

        @if ($order->quantity > 1)
            <div style="margin-top:24px;">
                <div class="label" style="margin-bottom:10px;">Senarai Peserta ({{ $names->filter()->count() }})</div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px 20px;">
                    @foreach ($names as $i => $name)
                        <div style="display:flex; align-items:center; gap:9px; font-size:13px; color:#1A1D21; padding:7px 0; border-bottom:1px solid #F1F5F4;">
                            <span style="width:22px; height:22px; border-radius:6px; background:#edeee0; color:#42481c; font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0;">{{ $i + 1 }}</span>{{ $name ?: '—' }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div style="margin-top:40px; display:flex; flex-direction:column; align-items:center; text-align:center;">
            <div style="width:150px; height:150px; border:1px solid #E2E8F0; border-radius:12px; padding:8px; background:#fff;">{!! \App\Support\PdfAssets::qr($order->trackingUrl()) !!}</div>
            <div style="font-size:12.5px; color:#64748B; margin-top:12px;">Imbas QR untuk jejak status tempahan anda secara langsung</div>
            <div style="display:inline-flex; align-items:center; gap:7px; margin-top:12px; background:#42481c; color:#fff; border-radius:9px; padding:10px 20px; font-size:13px; font-weight:700;">{{ $company['website'] ?? 'www.nadiqurban.com' }}</div>
        </div>

        <div style="margin-top:auto; padding-top:32px; border-top:1px solid #E2E8F0; text-align:center; font-size:11px; color:#94A3AC;">Dokumen ini dijana secara automatik oleh Nadi Qurban OSI · {{ $order->order_no }} · Sah tanpa tandatangan basah.</div>
    </div>
</x-pdf.layout>
