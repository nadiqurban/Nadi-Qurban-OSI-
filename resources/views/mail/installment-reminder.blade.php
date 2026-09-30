<!DOCTYPE html>
<html lang="ms">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0; background:#EEF1EC; font-family:Inter, Arial, sans-serif; color:#1A1D21;">
    <div style="max-width:520px; margin:0 auto; padding:24px 16px;">
        <div style="font-size:15px; font-weight:800; letter-spacing:.3px; color:#42481c;">NADI QURBAN</div>
        <div style="font-size:10px; font-weight:600; letter-spacing:2px; color:#94A3AC; margin-top:3px;">PORTAL BAYARAN ANSURAN</div>
        <div style="background:#fff; border:1px solid #E2E8F0; border-radius:14px; padding:22px 24px; margin-top:18px;">
            <p style="font-size:14px; margin:0 0 12px;">Assalamualaikum {{ $plan->customer->name }},</p>
            <p style="font-size:13.5px; line-height:1.6; color:#334155; margin:0 0 14px;">
                @if ($when === 'before')
                    Ini peringatan mesra bahawa <b>ansuran ke-{{ $installment->seq }}</b> bagi tempahan <b>{{ $plan->order_no }}</b> berjumlah <b>{{ rm($installment->amount_sen) }}</b> perlu dibayar pada <b>{{ tarikh($installment->due_date) }}</b>.
                @else
                    <b>Ansuran ke-{{ $installment->seq }}</b> bagi tempahan <b>{{ $plan->order_no }}</b> berjumlah <b>{{ rm($installment->amount_sen) }}</b> telah melepasi tarikh akhir ({{ tarikh($installment->due_date) }}). Sila jelaskan secepat mungkin.
                @endif
            </p>
            <a href="{{ $plan->portalUrl() }}" style="display:block; text-align:center; background:#42481c; color:#fff; text-decoration:none; border-radius:11px; padding:14px; font-size:14px; font-weight:700;">Bayar Sekarang</a>
            <p style="font-size:12px; color:#94A3AC; margin:14px 0 0;">Baki semasa: {{ rm($plan->balanceSen()) }} · Pautan ini kekal untuk semua ansuran anda.</p>
        </div>
        <p style="font-size:11px; color:#94A3AC; text-align:center; margin-top:14px;">nadiqurban.com · {{ app(\App\Support\Settings::class)->get('company.name', 'Nadi Qurban Sdn. Bhd.') }}</p>
    </div>
</body>
</html>
