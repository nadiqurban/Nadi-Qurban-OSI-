<?php

namespace App\Support;

use App\Enums\InstallmentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\VendorPaymentStatus;
use App\Models\Installment;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Payment;
use App\Models\VendorPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Figures for Kewangan (KPIs, Aliran Tunai bars, Kaedah Bayaran donut).
 *
 * Kutipan (collections) = verified order payments + paid instalments +
 * payments on stand-alone invoices (invoices linked to an order are already
 * counted through the order's payment). Bayaran = completed vendor payments.
 */
final class FinanceStats
{
    /** Donut buckets & colours (Kewangan.dc.html mData). */
    public const METHODS = [
        'FPX Online' => '#42481c',
        'Kad Kredit' => '#C9A227',
        'DuitNow QR' => '#8a924e',
        'Pindahan Bank' => '#b1b67c',
    ];

    /** Chart month labels (design: Jan … Ogo). */
    private const MONTHS = ['Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ogo', 'Sep', 'Okt', 'Nov', 'Dis'];

    /** @var Collection<int, array{amount: int, at: Carbon, method: string}>|null */
    private ?Collection $inflows = null;

    /** @return Collection<int, array{amount: int, at: Carbon, method: string}> */
    public function inflows(): Collection
    {
        if ($this->inflows !== null) {
            return $this->inflows;
        }

        $orders = Payment::query()->where('status', PaymentStatus::Verified)->get(['amount_sen', 'paid_at', 'verified_at', 'method', 'channel'])
            ->map(fn (Payment $p) => [
                'amount' => $p->amount_sen,
                'at' => $p->paid_at ?? $p->verified_at ?? now(),
                'method' => self::bucket($p->method === PaymentMethod::Fpx ? ($p->channel ?: 'FPX') : 'Pindahan Bank'),
            ]);

        $installments = Installment::query()->where('status', InstallmentStatus::Paid)->whereNotNull('paid_at')->get(['amount_sen', 'paid_at', 'method'])
            ->map(fn (Installment $i) => ['amount' => $i->amount_sen, 'at' => $i->paid_at ?? now(), 'method' => self::bucket((string) $i->method)]);

        $invoices = InvoicePayment::query()->whereHas('invoice', fn ($q) => $q->whereNull('order_id'))->get(['amount_sen', 'paid_at', 'method'])
            ->map(fn (InvoicePayment $p) => ['amount' => $p->amount_sen, 'at' => $p->paid_at, 'method' => self::bucket($p->method)]);

        return $this->inflows = $orders->concat($installments)->concat($invoices)->values();
    }

    /** Map any channel/method string to one of the four donut buckets. */
    public static function bucket(string $method): string
    {
        $m = mb_strtolower($method);

        return match (true) {
            str_contains($m, 'duitnow') || str_contains($m, 'qr') => 'DuitNow QR',
            str_contains($m, 'kad') || str_contains($m, 'card') || str_contains($m, 'kredit') => 'Kad Kredit',
            str_contains($m, 'fpx') => 'FPX Online',
            default => 'Pindahan Bank',
        };
    }

    /**
     * Four KPI cards.
     *
     * @return list<array{icon: string, tone: string, value: string, label: string, delta: string, trend: string}>
     */
    public function kpis(): array
    {
        $in = $this->inflows();
        $now = now();
        // 30-day period vs the previous 30 days; no comparison base → neutral "—".
        $growth = fn (int $cur, int $prev) => $prev > 0 ? ($cur - $prev) / $prev * 100 : null;
        $delta = fn (?float $pct) => $pct === null ? '—' : ($pct >= 0 ? '↑ ' : '↓ ').(abs($pct) >= 1000 ? '999+' : number_format(abs($pct), 1)).'%';
        $trend = fn (?float $pct) => $pct === null ? 'flat' : ($pct >= 0 ? 'up' : 'down');

        $inCur = (int) $in->filter(fn ($r) => $r['at']->gte($now->copy()->subDays(30)))->sum('amount');
        $inPrev = (int) $in->filter(fn ($r) => $r['at']->lt($now->copy()->subDays(30)) && $r['at']->gte($now->copy()->subDays(60)))->sum('amount');
        $inGrowth = $growth($inCur, $inPrev);

        $vendor = VendorPayment::query()->where('status', VendorPaymentStatus::Completed);
        $outTotal = (int) (clone $vendor)->sum('amount_sen');
        $outCur = (int) (clone $vendor)->where('payment_date', '>=', $now->copy()->subDays(30)->toDateString())->sum('amount_sen');
        $outPrev = (int) (clone $vendor)->whereBetween('payment_date', [$now->copy()->subDays(60)->toDateString(), $now->copy()->subDays(31)->toDateString()])->sum('amount_sen');
        $outGrowth = $growth($outCur, $outPrev);

        $unpaid = Invoice::query()->where('status', '!=', InvoiceStatus::Paid);
        $unpaidCount = (clone $unpaid)->count();
        $unpaidSen = (int) (clone $unpaid)->selectRaw('COALESCE(SUM(total_sen - paid_sen), 0) as bal')->value('bal');

        $overdue = (clone $unpaid)->where('due_date', '<', today()->subDays(30));
        $overdueSen = (int) (clone $overdue)->selectRaw('COALESCE(SUM(total_sen - paid_sen), 0) as bal')->value('bal');
        $overdueCount = (clone $overdue)->count();

        return [
            ['icon' => 'trend-up', 'tone' => 'primary', 'value' => rm_short((int) $in->sum('amount')), 'label' => 'Jumlah Kutipan', 'delta' => $delta($inGrowth), 'trend' => $trend($inGrowth)],
            ['icon' => 'arrow-circle-down', 'tone' => 'gold', 'value' => rm_short($outTotal), 'label' => 'Bayaran Vendor', 'delta' => $delta($outGrowth), 'trend' => $trend($outGrowth)],
            ['icon' => 'hourglass-medium', 'tone' => 'warning', 'value' => rm_short($unpaidSen), 'label' => 'Belum Dijelaskan', 'delta' => $unpaidCount.' invois', 'trend' => 'warn'],
            ['icon' => 'warning-circle', 'tone' => 'danger', 'value' => rm_short($overdueSen), 'label' => 'Tertunggak > 30 hari', 'delta' => $overdueCount.' invois', 'trend' => 'down'],
        ];
    }

    /**
     * Last `$months` months of collections vs vendor payments, in RM '000.
     *
     * @return list<array{label: string, in: float, out: float}>
     */
    public function cashflow(int $months = 8): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);
        $in = $this->inflows()->filter(fn ($r) => $r['at']->gte($start))->groupBy(fn ($r) => $r['at']->format('Y-m'))->map(fn ($g) => (int) $g->sum('amount'));
        $out = VendorPayment::query()->where('status', VendorPaymentStatus::Completed)->where('payment_date', '>=', $start->toDateString())
            ->get(['amount_sen', 'payment_date'])
            ->groupBy(fn (VendorPayment $p) => $p->payment_date?->format('Y-m'))->map(fn ($g) => (int) $g->sum('amount_sen'));

        $rows = [];
        for ($i = 0; $i < $months; $i++) {
            $m = $start->copy()->addMonths($i);
            $key = $m->format('Y-m');
            $rows[] = [
                'label' => self::MONTHS[$m->month - 1],
                'in' => round(($in[$key] ?? 0) / 100 / 1000, 1),
                'out' => round(($out[$key] ?? 0) / 100 / 1000, 1),
            ];
        }

        return $rows;
    }

    /**
     * Donut slices (share of inbound transactions by count, as in the design).
     *
     * @return list<array{name: string, color: string, value: int, pct: string}>
     */
    public function methods(): array
    {
        $counts = $this->inflows()->countBy('method');
        $total = max(1, $counts->sum());

        return collect(self::METHODS)->map(fn (string $color, string $name) => [
            'name' => $name,
            'color' => $color,
            'value' => (int) ($counts[$name] ?? 0),
            'pct' => round(($counts[$name] ?? 0) / $total * 100).'%',
        ])->values()->all();
    }

    public function totalCollected(): int
    {
        return (int) $this->inflows()->sum('amount');
    }
}
