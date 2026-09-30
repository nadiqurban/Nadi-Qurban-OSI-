@php
    /** @var \App\Models\InstallmentPlan $plan  with customer, installments */
    /** @var \App\Models\PaymentGatewayTransaction $tx */
    $c = $plan->customer;
    $k = 'font-size:11px; color:#94A3AC;';
    $v = 'font-size:13px; font-weight:600; margin-top:2px; color:#1A1D21;';
@endphp
{{-- Portal Ansuran receipt (#ans-rcpt), A5 --}}
<div style="display:flex; align-items:center; gap:12px; padding-bottom:16px; border-bottom:2px solid #42481c;">
    <div style="width:44px; height:44px; border-radius:10px; overflow:hidden;"><img src="{{ \App\Support\PdfAssets::image('images/logo-128.png') }}" alt="Nadi Qurban" style="width:100%; height:100%; object-fit:cover; display:block;"></div>
    <div style="flex:1;"><div style="font-size:14px; font-weight:800; color:#42481c;">NADI QURBAN SDN. BHD.</div><div style="font-size:10px; font-weight:600; letter-spacing:2px; color:#94A3AC;">{{ app(\App\Support\Settings::class)->get('company.ssm', '1677511-A') }}</div></div>
    <div style="text-align:right;"><div style="font-size:15px; font-weight:800; color:#1A1D21;">RESIT</div><div style="font-size:11.5px; font-weight:700; color:#42481c; margin-top:2px;">{{ $tx->reference }}</div></div>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:11px 16px; margin-top:16px;">
    <div><div style="{{ $k }}">No. Tempahan</div><div style="{{ $v }}">{{ $plan->order_no }}</div></div>
    <div><div style="{{ $k }}">Pelanggan</div><div style="{{ $v }}">{{ $c->name }}</div></div>
    <div><div style="{{ $k }}">No. Telefon</div><div style="{{ $v }}">{{ $c->phone ?: '-' }}</div></div>
    <div><div style="{{ $k }}">Emel</div><div style="{{ $v }}">{{ $c->email ?: '-' }}</div></div>
    <div style="grid-column:1 / -1;"><div style="{{ $k }}">Alamat</div><div style="{{ $v }}">{{ collect([$c->address, trim($c->postcode.' '.$c->city), $c->state])->filter()->implode(', ') ?: '-' }}</div></div>
    <div><div style="{{ $k }}">Kaedah</div><div style="{{ $v }}">{{ $tx->method?->shortLabel() ?? '-' }}</div></div>
    <div><div style="{{ $k }}">Pakej</div><div style="{{ $v }}">{{ $plan->package_name }}</div></div>
    <div><div style="{{ $k }}">Tarikh</div><div style="{{ $v }}">{{ tarikh($tx->paid_at ?? $tx->created_at, true) }}</div></div>
    <div><div style="{{ $k }}">Ansuran</div><div style="{{ $v }}">{{ $plan->installments->whereIn('id', $tx->installment_ids)->pluck('seq')->map(fn ($s) => 'ke-'.$s)->implode(', ') }}</div></div>
</div>
<div style="display:flex; justify-content:space-between; align-items:center; background:#F6F8F7; border-radius:10px; padding:14px 16px; margin-top:18px;"><span style="font-size:13px; color:#64748B;">Jumlah Dibayar</span><span style="font-size:20px; font-weight:800; color:#42481c;">{{ rm($tx->amount_sen) }}</span></div>
<div style="display:flex; justify-content:space-between; margin-top:12px; font-size:12.5px;"><span style="color:#94A3AC;">Baki Baharu</span><span style="font-weight:700; color:#1A1D21;">{{ rm($plan->balanceSen()) }}</span></div>
@if ($plan->quantity > 1)
    <div style="margin-top:16px;"><div style="font-size:11px; font-weight:700; letter-spacing:.4px; color:#94A3AC; text-transform:uppercase; margin-bottom:9px;">Senarai Peserta</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:7px 16px;">
            @foreach ($plan->participantList() as $n => $name)
                <div style="display:flex; align-items:center; gap:8px; font-size:12.5px; color:#1A1D21;"><span style="width:20px; height:20px; border-radius:6px; background:#edeee0; color:#42481c; font-size:10.5px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0;">{{ $n + 1 }}</span>{{ $name }}</div>
            @endforeach
        </div>
    </div>
@endif
<div style="margin-top:20px; padding-top:14px; border-top:1px solid #E2E8F0; text-align:center; font-size:10.5px; color:#94A3AC;">Disahkan oleh CHIP IN · nadiqurban.com · Sah tanpa tandatangan basah.</div>
