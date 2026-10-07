@php
    /**
     * Invois Komisen (Pengurusan Ejen.dc.html #comm-inv).
     *
     * @var \Illuminate\Support\Collection<int, array{agent: \App\Models\Agent, count: int, sales_sen: int, commission_sen: int}> $rows
     * @var \App\Support\Period $period
     * @var string $number
     */
    $company = app(\App\Support\Settings::class)->group('company');
    $rows = $rows->filter(fn ($r) => $r['commission_sen'] > 0)->values();
    $th = 'background:#42481c; color:#fff; font-size:10px; letter-spacing:.4px; text-transform:uppercase; padding:9px 10px;';
    $td = 'padding:9px 10px; font-size:11.5px; border-bottom:1px solid #E2E8F0; vertical-align:top;';
@endphp
<div style="font-family:Inter,Arial,sans-serif; color:#1A1D21; display:flex; flex-direction:column; min-height:100%;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; border-bottom:2px solid #42481c; padding-bottom:16px;">
        <div style="display:flex; align-items:center; gap:12px;">
            <img src="{{ \App\Support\PdfAssets::image('images/logo-128.png') }}" alt="" style="width:50px; height:50px; border-radius:10px; object-fit:cover;">
            <div>
                <div style="font-size:16px; font-weight:800; color:#42481c;">{{ mb_strtoupper($company['name'] ?? 'Nadi Qurban Sdn. Bhd.') }}</div>
                <div style="font-size:10.5px; color:#94A3AC; margin-top:2px;">No. SSM: {{ $company['ssm'] ?? '' }}</div>
                <div style="font-size:10.5px; color:#64748B; margin-top:3px;">{{ $company['address'] ?? '' }}</div>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:20px; font-weight:800; letter-spacing:.5px;">INVOIS KOMISEN</div>
            <div style="font-size:12px; font-weight:700; color:#42481c; margin-top:4px;">{{ $number }}</div>
            <div style="font-size:11px; color:#64748B; margin-top:2px;">Tarikh: {{ now()->format('d/m/Y') }}</div>
            <div style="font-size:11px; color:#64748B; margin-top:2px;">Tempoh: <b style="color:#1A1D21;">{{ $period->label() }}</b></div>
        </div>
    </div>

    <table style="width:100%; border-collapse:collapse; margin-top:18px;">
        <thead>
            <tr>
                <th style="{{ $th }} text-align:left;">No.</th>
                <th style="{{ $th }} text-align:left;">Ejen</th>
                <th style="{{ $th }} text-align:left;">Maklumat Bank</th>
                <th style="{{ $th }} text-align:center;">Tempahan</th>
                <th style="{{ $th }} text-align:right;">Jualan (RM)</th>
                <th style="{{ $th }} text-align:right;">Komisen (RM)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $i => $r)
                <tr>
                    <td style="{{ $td }}">{{ $i + 1 }}</td>
                    <td style="{{ $td }}"><b>{{ $r['agent']->user->name }}</b><div style="font-size:10px; color:#94A3AC; font-family:monospace;">{{ $r['agent']->code }}</div></td>
                    <td style="{{ $td }}">{{ $r['agent']->bank_name ?? '-' }}<div style="font-size:10px; color:#64748B;">{{ collect([$r['agent']->bank_account_name, $r['agent']->bank_account_no])->filter()->implode(' · ') ?: '-' }}</div></td>
                    <td style="{{ $td }} text-align:center;">{{ $r['count'] }}</td>
                    <td style="{{ $td }} text-align:right;">{{ number_format($r['sales_sen'] / 100, 2) }}</td>
                    <td style="{{ $td }} text-align:right; font-weight:700; color:#9a7b12;">{{ number_format($r['commission_sen'] / 100, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="{{ $td }} text-align:center; color:#94A3AC; padding:24px;">Tiada komisen bagi tempoh ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div style="display:flex; justify-content:flex-end; margin-top:16px;">
        <div style="width:280px;">
            <div style="display:flex; justify-content:space-between; font-size:12px; color:#64748B; padding:5px 0;"><span>Jumlah Jualan</span><span>{{ rm($rows->sum('sales_sen'), true) }}</span></div>
            <div style="display:flex; justify-content:space-between; font-size:15px; font-weight:800; color:#42481c; border-top:2px solid #42481c; padding-top:8px; margin-top:4px;"><span>Jumlah Komisen</span><span>{{ rm($rows->sum('commission_sen'), true) }}</span></div>
        </div>
    </div>

    <div style="margin-top:46px; display:grid; grid-template-columns:1fr 1fr; gap:30px;">
        <div style="border-top:1px solid #94A3AC; padding-top:6px; font-size:11px; color:#64748B;">Disediakan oleh</div>
        <div style="border-top:1px solid #94A3AC; padding-top:6px; font-size:11px; color:#64748B;">Diluluskan oleh (Kewangan)</div>
    </div>
    <div style="margin-top:30px; text-align:center; font-size:10px; color:#94A3AC;">Dijana oleh {{ $company['name'] ?? 'Nadi Qurban Sdn Bhd' }} &middot; {{ $number }}</div>
</div>
