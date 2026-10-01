@php
    /** @var object $doc  Invoice::toDocument() / Quotation::toDocument() / FinanceDocForm::document() */
    $isInvoice = $doc->type === 'invoice';
    $money = fn (int $sen) => 'RM '.number_format($sen / 100, 2);
    $th = 'background:#42481c; color:#fff; font-size:11px; letter-spacing:.4px; text-transform:uppercase; padding:11px 14px;';
    $td = 'padding:13px 14px; font-size:13px; border-bottom:1px solid #E2E8F0;';
    $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d-m-Y') : '-';
@endphp
{{-- Kewangan.dc.html #inv-a4 / #quote-a4 --}}
<div style="font-family:Inter,Arial,sans-serif; color:#1A1D21;">
    <div style="display:flex; align-items:flex-start; justify-content:space-between; border-bottom:2px solid #42481c; padding-bottom:20px;">
        <div style="display:flex; align-items:center; gap:12px;">
            <img src="{{ \App\Support\PdfAssets::image('images/logo-128.png') }}" alt="" style="width:52px; height:52px; border-radius:11px; object-fit:cover;">
            <div>
                <div style="font-size:17px; font-weight:800; color:#42481c;">{{ $doc->company['name'] ?? '' }}</div>
                <div style="font-size:11px; color:#94A3AC; margin-top:2px;">No. SSM: {{ $doc->company['ssm'] ?? '' }}</div>
                <div style="font-size:11px; color:#64748B; margin-top:4px; line-height:1.5;">{{ $doc->company['address'] ?? '' }}<br>{{ $doc->company['phone'] ?? '' }} · {{ $doc->company['email'] ?? '' }}</div>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:24px; font-weight:800; letter-spacing:1px; color:#1A1D21;">{{ $isInvoice ? 'INVOIS' : 'QUOTATION' }}</div>
            <div style="font-size:13px; font-weight:700; color:#42481c; margin-top:4px;">{{ $doc->number }}</div>
            <div style="font-size:12px; color:#64748B; margin-top:2px;">Tarikh: {{ $date($doc->date) }}</div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-top:24px;">
        <div>
            <div style="font-size:11px; font-weight:700; letter-spacing:.5px; color:#94A3AC; text-transform:uppercase; margin-bottom:7px;">Kepada</div>
            <div style="font-size:13.5px; color:#334155; line-height:1.6;">
                <b>{{ $doc->name }}</b>
                @if ($doc->phone)<br>{{ $doc->phone }}@endif
                @if ($doc->email)<br>{{ $doc->email }}@endif
                @if ($doc->address)<br>{{ $doc->address }}@endif
                @if ($isInvoice)<br>No. Tempahan: {{ $doc->order ?: '-' }}@endif
            </div>
        </div>
        @if ($isInvoice)
            <div>
                <div style="font-size:11px; font-weight:700; letter-spacing:.5px; color:#94A3AC; text-transform:uppercase; margin-bottom:7px;">Butiran</div>
                <div style="font-size:13.5px; color:#334155; line-height:1.6;">Tarikh Tempoh: {{ $date($doc->due) }}</div>
            </div>
        @endif
    </div>

    <table style="width:100%; border-collapse:collapse; margin-top:26px;">
        <thead><tr>
            <th style="{{ $th }} text-align:left;">{{ $isInvoice ? 'Perkara' : 'Servis / Pakej' }}</th>
            <th style="{{ $th }} text-align:center;">Kuantiti</th>
            <th style="{{ $th }} text-align:right;">Harga Seunit</th>
            <th style="{{ $th }} text-align:right;">Jumlah</th>
        </tr></thead>
        <tbody>
            @foreach ($doc->items as $it)
                <tr>
                    <td style="{{ $td }}">{{ $it['description'] }}</td>
                    <td style="{{ $td }} text-align:center;">{{ $it['quantity'] }}</td>
                    <td style="{{ $td }} text-align:right;">{{ $money($it['unit_price_sen']) }}</td>
                    <td style="{{ $td }} text-align:right; font-weight:600;">{{ $money($it['quantity'] * $it['unit_price_sen']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="display:flex; justify-content:flex-end; margin-top:18px;"><div style="width:280px;">
        @if ($isInvoice)
            <div style="display:flex; justify-content:space-between; font-size:13px; color:#64748B;"><span>Subjumlah</span><span>{{ $money($doc->total_sen) }}</span></div>
            <div style="display:flex; justify-content:space-between; font-size:13px; color:#64748B; margin-top:8px;"><span>Cukai (0%)</span><span>RM 0.00</span></div>
            <div style="display:flex; justify-content:space-between; font-size:16px; font-weight:800; color:#42481c; border-top:2px solid #42481c; padding-top:10px; margin-top:10px;"><span>Jumlah</span><span>{{ $money($doc->total_sen) }}</span></div>
        @else
            <div style="display:flex; justify-content:space-between; font-size:16px; font-weight:800; color:#42481c; border-top:2px solid #42481c; padding-top:10px;"><span>Jumlah Sebut Harga</span><span>{{ $money($doc->total_sen) }}</span></div>
        @endif
    </div></div>

    @if ($doc->note)
        <div style="margin-top:30px; padding-top:16px; border-top:1px solid #E2E8F0;">
            <div style="font-size:11px; font-weight:700; letter-spacing:.5px; color:#94A3AC; text-transform:uppercase; margin-bottom:6px;">Nota</div>
            <div style="font-size:12.5px; color:#475569; line-height:1.6; white-space:pre-line;">{{ $doc->note }}</div>
        </div>
    @endif
    <div style="margin-top:36px; text-align:center; font-size:11px; color:#94A3AC;">Dijana oleh {{ $doc->company['name'] ?? 'Nadi Qurban Sdn Bhd' }} · nadiqurban.com · Sah tanpa tandatangan basah.</div>
</div>
