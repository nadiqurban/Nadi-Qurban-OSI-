@php
    /** @var \App\Models\InstallmentPlan $plan  with customer, installments */
    $c = $plan->customer;
    $paid = $plan->paidCount();
    $baki = $plan->balanceSen();
    $th = 'background:#42481c; color:#fff; font-size:11px; letter-spacing:.4px; text-transform:uppercase; padding:10px 12px;';
    $td = 'padding:11px 12px; font-size:13px; border-bottom:1px solid #E2E8F0;';
    $k = 'font-size:11px; color:#94A3AC;';
    $v = 'font-size:13px; font-weight:600; margin-top:2px; color:#1A1D21;';
@endphp
{{-- Resit Bayaran Ansuran A4 (Bayaran Ansuran.dc.html #ans-receipt) --}}
<div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding-bottom:18px; border-bottom:2px solid #42481c;">
    <div style="display:flex; align-items:center; gap:12px;">
        <div style="width:46px; height:46px; border-radius:10px; overflow:hidden;"><img src="{{ \App\Support\PdfAssets::image('images/logo-128.png') }}" alt="Nadi Qurban" style="width:100%; height:100%; object-fit:cover; display:block;"></div>
        <div><div style="font-size:15px; font-weight:800; color:#42481c;">{{ mb_strtoupper(app(\App\Support\Settings::class)->get('company.name', 'Nadi Qurban Sdn. Bhd.')) }}</div><div style="font-size:10px; font-weight:600; letter-spacing:2px; color:#94A3AC;">{{ app(\App\Support\Settings::class)->get('company.ssm', '1677511-A') }}</div></div>
    </div>
    <div style="text-align:right;"><div style="font-size:17px; font-weight:800; letter-spacing:.5px; color:#1A1D21;">RESIT</div><div style="font-size:12px; font-weight:700; color:#42481c; margin-top:3px;">{{ $plan->receiptNo() }}</div><div style="font-size:11.5px; color:#64748B; margin-top:2px;">{{ now()->format('d-m-Y') }}</div></div>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px 18px; margin-top:18px;">
    <div><div style="{{ $k }}">No. Tempahan</div><div style="{{ $v }}">{{ $plan->order_no }}</div></div>
    <div><div style="{{ $k }}">Pelanggan</div><div style="{{ $v }}">{{ $c->name }}</div></div>
    <div><div style="{{ $k }}">Pakej</div><div style="{{ $v }}">{{ $plan->package_name }} · {{ $plan->ibadahLabel() }}</div></div>
    <div><div style="{{ $k }}">Ansuran Dibayar</div><div style="{{ $v }}">{{ $paid }} / {{ $plan->months }} bulan</div></div>
    <div><div style="{{ $k }}">No. Telefon</div><div style="{{ $v }}">{{ $c->phone ?: '-' }}</div></div>
    <div><div style="{{ $k }}">Emel</div><div style="{{ $v }}">{{ $c->email ?: '-' }}</div></div>
    <div><div style="{{ $k }}">Jumlah Bahagian</div><div style="{{ $v }}">{{ $plan->quantity }} bahagian</div></div>
    <div style="grid-column:1 / -1;"><div style="{{ $k }}">Alamat Penuh</div><div style="{{ $v }}">{{ collect([$c->address, trim($c->postcode.' '.$c->city), $c->state])->filter()->implode(', ') ?: '-' }}</div></div>
</div>
<table style="width:100%; border-collapse:collapse; margin-top:22px;">
    <thead><tr><th style="{{ $th }} text-align:left;">Butiran</th><th style="{{ $th }} text-align:right;">Jumlah</th></tr></thead>
    <tbody>
        <tr><td style="{{ $td }}">Ansuran bulanan</td><td style="{{ $td }} text-align:right;">{{ rm($plan->monthly_sen) }}</td></tr>
        @if ($plan->deposit_sen > 0)
            <tr><td style="{{ $td }}">Deposit</td><td style="{{ $td }} text-align:right;">{{ rm($plan->deposit_sen) }}</td></tr>
        @endif
        <tr><td style="{{ $td }}">Jumlah telah dibayar ({{ $paid }} bulan)</td><td style="{{ $td }} text-align:right; font-weight:600;">{{ rm($plan->paidSen()) }}</td></tr>
        <tr><td style="{{ $td }}">Baki belum bayar ({{ $plan->months - $paid }} bulan)</td><td style="{{ $td }} text-align:right; font-weight:700; color:{{ $baki > 0 ? '#DC2626' : '#16A34A' }};">{{ rm($baki) }}</td></tr>
        <tr><td style="padding:11px 12px; font-size:13px;">Jumlah pelan keseluruhan</td><td style="padding:11px 12px; font-size:13px; text-align:right;">{{ rm($plan->total_sen) }}</td></tr>
        @if ($plan->promo_code && $plan->discount_sen > 0)
            <tr><td style="padding:11px 12px; font-size:13px; color:#16A34A;">Kod Promosi ({{ $plan->promo_code }})</td><td style="padding:11px 12px; font-size:13px; text-align:right; color:#16A34A; font-weight:600;">- {{ rm($plan->discount_sen) }}</td></tr>
        @endif
    </tbody>
</table>
@if ($plan->quantity > 1)
    <div style="margin-top:22px;">
        <div style="font-size:12px; font-weight:700; letter-spacing:.4px; color:#94A3AC; text-transform:uppercase; margin-bottom:10px;">Senarai Peserta</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px 18px;">
            @foreach ($plan->participantList() as $n => $name)
                <div style="display:flex; align-items:center; gap:9px; font-size:13px; color:#1A1D21;"><span style="width:22px; height:22px; border-radius:6px; background:#edeee0; color:#42481c; font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0;">{{ $n + 1 }}</span>{{ $name }}</div>
            @endforeach
        </div>
    </div>
@endif
<div style="margin-top:24px; padding-top:14px; border-top:1px solid #E2E8F0; text-align:center; font-size:11px; color:#94A3AC;">Dijana automatik oleh Nadi Qurban OSI · {{ $plan->receiptNo() }} · nadiqurban.com · Sah tanpa tandatangan basah.</div>
