@php
    /**
     * Resit Bayaran of a public booking (Tempahan Awam.dc.html #pay-rcpt).
     *
     * @var \App\Models\Order $order
     */
    $company = app(\App\Support\Settings::class)->group('company');
    $paid = $order->payment?->status === \App\Enums\PaymentStatus::Verified;
    $statusLabel = match (true) {
        $paid => 'Dibayar',
        $order->status === \App\Enums\OrderStatus::AwaitingPayment => 'Menunggu Bayaran',
        $order->payment?->status === \App\Enums\PaymentStatus::Rejected => 'Ditolak',
        default => 'Menunggu Pengesahan',
    };
    $customer = $order->customer;
    $address = collect([$customer->address, $customer->postcode, $customer->city, $customer->state])->filter()->implode(', ');
    $trackUrl = url('/jejak').'?track='.$order->tracking_no;
    $names = $order->participants->pluck('name')->filter()->values();
    $th = 'background:#42481c; color:#fff; font-size:11px; letter-spacing:.4px; text-transform:uppercase; padding:10px 12px;';
@endphp
<div style="font-family:Inter,Arial,sans-serif; color:#1A1D21; display:flex; flex-direction:column; min-height:257mm;">
    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding-bottom:18px; border-bottom:2px solid #42481c;">
        <div style="display:flex; align-items:center; gap:12px;">
            <img src="{{ \App\Support\PdfAssets::image('images/logo-128.png') }}" alt="" style="width:48px; height:48px; border-radius:11px; object-fit:cover;">
            <div>
                <div style="font-size:16px; font-weight:800; color:#42481c;">{{ mb_strtoupper($company['name'] ?? 'Nadi Qurban Sdn Bhd') }}</div>
                <div style="font-size:10.5px; color:#94A3AC; margin-top:2px;">{{ $company['ssm'] ?? '' }}</div>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:20px; font-weight:800; letter-spacing:1px;">RESIT BAYARAN</div>
            <div style="font-size:12.5px; font-weight:700; color:#42481c; margin-top:4px;">{{ $order->order_no }}</div>
            <div style="font-size:11.5px; color:#64748B; margin-top:2px;">No. Tracking: <b style="color:#1A1D21;">{{ $order->tracking_no }}</b></div>
            <div style="font-size:12px; color:#64748B; margin-top:2px;">{{ $order->created_at->translatedFormat('d M Y, h:i A') }}</div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px 22px; margin-top:18px;">
        @foreach ([
            ['Nama Pelanggan', $customer->name, false], ['No. Telefon', $customer->phone, false],
            ['Emel', $customer->email ?: '-', false], ['Kaedah Bayaran', $order->payment_method === \App\Enums\PaymentMethod::Fpx ? 'FPX Payment (CHIP)' : $order->payment_method->label(), false],
            ['Alamat', $address ?: '-', true],
            ['Negara Pelaksanaan', $order->country->name, false], ['Status', $statusLabel, false],
        ] as [$k, $v, $wide])
            <div style="{{ $wide ? 'grid-column:1 / -1;' : '' }}"><div style="font-size:11px; color:#94A3AC;">{{ $k }}</div><div style="font-size:13px; font-weight:600; margin-top:2px; line-height:1.5;">{{ $v }}</div></div>
        @endforeach
    </div>

    <table style="width:100%; border-collapse:collapse; margin-top:20px;">
        <thead><tr><th style="{{ $th }} text-align:left;">Perkara</th><th style="{{ $th }} text-align:center;">Kuantiti</th><th style="{{ $th }} text-align:right;">Jumlah</th></tr></thead>
        <tbody><tr>
            <td style="padding:12px; font-size:13px; border-bottom:1px solid #E2E8F0;">{{ $order->product_name }} — {{ $order->package_name }}</td>
            <td style="padding:12px; font-size:13px; border-bottom:1px solid #E2E8F0; text-align:center;">{{ $order->quantity }}</td>
            <td style="padding:12px; font-size:13px; border-bottom:1px solid #E2E8F0; text-align:right; font-weight:600;">{{ rm($order->subtotal_sen, true) }}</td>
        </tr></tbody>
    </table>
    <div style="display:flex; justify-content:flex-end; margin-top:12px;"><div style="width:260px; display:flex; flex-direction:column; gap:7px; font-size:13px;">
        <div style="display:flex; justify-content:space-between; color:#64748B;"><span>Subjumlah</span><span>{{ rm($order->subtotal_sen, true) }}</span></div>
        <div style="display:flex; justify-content:space-between; color:#16A34A;"><span>Diskaun {{ $order->promo_code ? '('.$order->promo_code.')' : '' }}</span><span>- {{ rm($order->discount_sen, true) }}</span></div>
        <div style="display:flex; justify-content:space-between; font-size:15px; font-weight:800; color:#42481c; border-top:2px solid #42481c; padding-top:8px;"><span>{{ $paid ? 'Jumlah Dibayar' : 'Jumlah Perlu Dibayar' }}</span><span>{{ rm($order->total_sen, true) }}</span></div>
    </div></div>

    @if ($names->count() > 1)
        <div style="margin-top:20px;">
            <div style="font-size:11px; font-weight:700; letter-spacing:.5px; color:#94A3AC; text-transform:uppercase; margin-bottom:8px;">Senarai Peserta</div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px 18px;">
                @foreach ($names as $i => $n)<div style="font-size:13px;">{{ $i + 1 }}. {{ $n }}</div>@endforeach
            </div>
        </div>
    @endif

    <div style="margin-top:22px; display:flex; align-items:center; gap:16px; padding:14px 16px; border:1px solid #E2E8F0; border-radius:11px;">
        <div style="width:92px; height:92px; flex-shrink:0;">{!! \App\Support\PdfAssets::qr($trackUrl, 92) !!}</div>
        <div style="min-width:0;">
            <div style="font-size:13.5px; font-weight:700; color:#1A1D21;">Jejak Status Tempahan Anda</div>
            <div style="font-size:12px; color:#64748B; margin-top:4px; line-height:1.5;">Imbas kod QR ini dan masukkan No. Tracking <b style="color:#42481c;">{{ $order->tracking_no }}</b> untuk semak kemajuan ibadah.</div>
            <div style="font-size:12px; font-weight:600; color:#42481c; margin-top:5px;">{{ preg_replace('#^https?://#', '', url('/jejak')) }}</div>
        </div>
    </div>
    <div style="margin-top:12px; display:flex; align-items:center; gap:8px; font-size:12px; color:#42481c; background:#FAFBF6; border:1px solid #E5E8D6; border-radius:9px; padding:10px 12px;">&#10003; Lafaz akad telah dibuat oleh pelanggan.</div>
    <div style="margin-top:auto; padding-top:12px; border-top:1px solid #E2E8F0; text-align:center; font-size:11px; color:#94A3AC;">Dijana oleh {{ $company['name'] ?? 'Nadi Qurban Sdn Bhd' }} &middot; Sah tanpa tandatangan basah.</div>
</div>
