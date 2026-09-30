@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Order> $orders */
    /** @var \App\Enums\Courier $courier */
    $company = app(\App\Support\Settings::class)->group('company');
    $date = now()->format('d-m-Y');
    $th = 'background:#42481c; color:#fff; text-align:left; font-size:11px; letter-spacing:.4px; text-transform:uppercase; padding:10px 12px;';
    $td = 'padding:11px 12px; font-size:12.5px; border-bottom:1px solid #E2E8F0;';
@endphp
<x-pdf.layout title="Waybill">
    @foreach ($orders as $order)
        @php $c = $order->customer; $wbNo = 'NQ-WB-'.$order->year.'-'.substr($order->order_no, -4); @endphp
        <div class="page" style="padding:44px 52px;">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:20px; padding-bottom:20px; border-bottom:2px solid #42481c;">
                @include('pdf.partials.company-header', ['company' => $company, 'withAddress' => false])
                <div style="text-align:right;">
                    <div style="font-size:20px; font-weight:800; letter-spacing:1px; color:#1A1D21;">WAYBILL</div>
                    <div style="font-size:13px; font-weight:700; color:#42481c; margin-top:4px;">{{ $wbNo }}</div>
                    <div style="font-size:12px; color:#64748B; margin-top:2px;">Tarikh: {{ $date }}</div>
                    <div style="font-size:12px; color:#64748B; margin-top:2px;">Kurier: <span style="font-weight:700; color:#42481c;">{{ $courier->label() }}</span></div>
                    <div style="font-size:12px; color:#64748B; margin-top:2px;">No. Konsainan: <span style="font-weight:700; color:#1A1D21;">{{ $courier->consignmentFor($order->order_no) }}</span></div>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:22px; margin-top:22px;">
                <div>
                    <div class="label" style="margin-bottom:8px;">Penghantar (Pengirim)</div>
                    <div style="font-size:12.5px; color:#334155; line-height:1.6;">{{ mb_strtoupper($company['name'] ?? '') }}<br>{{ $company['address'] ?? '' }}<br>Tel: {{ $company['phone'] ?? '' }}</div>
                </div>
                <div>
                    <div class="label" style="margin-bottom:8px;">Penerima</div>
                    <div style="font-size:12.5px; color:#334155; line-height:1.6;">{{ $c->name }}<br>{{ collect([$c->address, trim($c->postcode.' '.$c->city), $c->state])->filter()->implode(', ') ?: '-' }}<br>Tel: {{ $c->phone }}</div>
                </div>
            </div>
            <table style="margin-top:24px;">
                <thead><tr><th style="{{ $th }}">No. Tempahan</th><th style="{{ $th }}">Servis / Haiwan</th><th style="{{ $th }} text-align:center;">Kuantiti</th><th style="{{ $th }}">Negara Pelaksanaan</th></tr></thead>
                <tbody><tr>
                    <td style="{{ $td }} font-weight:600;">{{ $order->order_no }}</td>
                    <td style="{{ $td }}">{{ $order->ibadahLabel() }}</td>
                    <td style="{{ $td }} text-align:center;">{{ $order->quantity }}</td>
                    <td style="{{ $td }}">{{ $order->country->name }}</td>
                </tr></tbody>
            </table>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:26px; margin-top:44px;">
                <div style="border-top:1px solid #94A3AC; padding-top:8px;"><div style="font-size:11.5px; color:#94A3AC; margin-top:4px;">Tandatangan Pengirim &amp; Cop</div></div>
                <div style="border-top:1px solid #94A3AC; padding-top:8px;"><div style="font-size:11.5px; color:#94A3AC; margin-top:4px;">Tandatangan Penerima &amp; Tarikh</div></div>
            </div>
            <div style="margin-top:28px; padding-top:14px; border-top:1px solid #E2E8F0; text-align:center; font-size:11px; color:#94A3AC;">Dokumen ini dijana secara automatik oleh Nadi Qurban OSI · {{ $wbNo }} · {{ $company['website'] ?? '' }}</div>
        </div>
    @endforeach
</x-pdf.layout>
