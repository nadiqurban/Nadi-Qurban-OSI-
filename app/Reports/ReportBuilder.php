<?php

namespace App\Reports;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStage;
use App\Enums\OrderStatus;
use App\Enums\PoStatus;
use App\Enums\ReportType;
use App\Enums\VendorPaymentStatus;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\ReportExport;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Support\FinanceStats;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Data for each Pusat Laporan template: headings + rows (+ summary lines for PDF).
 *
 * @phpstan-type ReportData array{title: string, subtitle: string, headings: list<string>, rows: list<list<string|int|float>>, summary: array<string, string>, widths: list<int>}
 */
final class ReportBuilder
{
    /** @return ReportData */
    public function build(ReportExport $report): array
    {
        $filters = $report->filters ?? [];
        $from = Carbon::parse($filters['from'] ?? now()->startOfYear())->startOfDay();
        $to = Carbon::parse($filters['to'] ?? now())->endOfDay();
        $countries = array_map('intval', $filters['countries'] ?? []);

        $orders = Order::query()
            ->where('status', '!=', OrderStatus::Cancelled)
            ->whereBetween('created_at', [$from, $to])
            ->when($countries !== [], fn (Builder $q) => $q->whereIn('country_id', $countries));

        $data = match ($report->type) {
            ReportType::Sales => $this->sales($orders),
            ReportType::Country => $this->country($orders),
            ReportType::Vendor => $this->vendor($countries),
            ReportType::Finance => $this->finance($from, $to),
            ReportType::Certificates => $this->certificates($orders),
            ReportType::Participants => $this->participants($orders),
        };

        return $data + [
            'title' => $report->name,
            'subtitle' => 'Tempoh '.tarikh($from).' – '.tarikh($to),
        ];
    }

    /**
     * @param  Builder<Order>  $orders
     * @return array{headings: list<string>, rows: list<list<string|int|float>>, summary: array<string, string>, widths: list<int>}
     */
    private function sales(Builder $orders): array
    {
        $list = (clone $orders)->get(['id', 'created_at', 'total_sen', 'quantity', 'service']);
        $rows = $list->groupBy(fn (Order $o) => $o->created_at->format('Y-m'))->sortKeys()->map(fn ($g, string $ym) => [
            mb_substr(tarikh(Carbon::parse($ym.'-01')), 2),
            $g->count(),
            (int) $g->sum('quantity'),
            rm((int) $g->sum('total_sen')),
            rm((int) round($g->avg('total_sen'))),
        ])->values()->all();

        return [
            'headings' => ['Bulan', 'Bil. Tempahan', 'Bahagian / Ekor', 'Nilai Jualan', 'Purata Tempahan'],
            'rows' => $rows,
            'summary' => ['Jumlah tempahan' => (string) $list->count(), 'Jumlah jualan' => rm((int) $list->sum('total_sen'))],
            'widths' => [16, 16, 18, 18, 18],
        ];
    }

    /**
     * @param  Builder<Order>  $orders
     * @return array{headings: list<string>, rows: list<list<string|int|float>>, summary: array<string, string>, widths: list<int>}
     */
    private function country(Builder $orders): array
    {
        $list = (clone $orders)->with('country')->get(['id', 'country_id', 'total_sen', 'quantity', 'stage']);
        $rows = $list->groupBy('country_id')->map(fn ($g) => [
            $g->first()->country->name,
            $g->count(),
            (int) $g->sum('quantity'),
            $g->filter(fn (Order $o) => $o->stage->position() >= OrderStage::ReportVerified->position())->count(),
            rm((int) $g->sum('total_sen')),
        ])->sortByDesc(1)->values()->all();

        return [
            'headings' => ['Negara', 'Tempahan', 'Bahagian / Ekor', 'Dilaksanakan', 'Nilai'],
            'rows' => $rows,
            'summary' => ['Negara' => (string) count($rows), 'Jumlah tempahan' => (string) $list->count()],
            'widths' => [22, 12, 16, 14, 16],
        ];
    }

    /**
     * @param  list<int>  $countries
     * @return array{headings: list<string>, rows: list<list<string|int|float>>, summary: array<string, string>, widths: list<int>}
     */
    private function vendor(array $countries): array
    {
        $vendors = Vendor::query()->with(['country', 'purchaseOrders'])
            ->when($countries !== [], fn (Builder $q) => $q->whereIn('country_id', $countries))
            ->orderBy('rank')->orderBy('code')->get();

        $rows = $vendors->map(function (Vendor $v) {
            $pos = $v->purchaseOrders->where('status', '!=', PoStatus::Cancelled);
            $done = $pos->where('status', PoStatus::Completed)->count();

            return [
                $v->code, $v->name, $v->country->name, $pos->count(), $done,
                $pos->count() > 0 ? round($done / $pos->count() * 100).'%' : '-',
                $v->rating !== null ? (string) $v->rating : '-',
                rm((int) $pos->sum('total_rm_sen')),
            ];
        })->values()->all();

        return [
            'headings' => ['Kod', 'Vendor', 'Negara', 'PO', 'PO Selesai', 'Kadar Siap', 'Rating', 'Nilai PO'],
            'rows' => $rows,
            'summary' => ['Vendor' => (string) $vendors->count()],
            'widths' => [10, 26, 14, 8, 11, 11, 8, 16],
        ];
    }

    /** @return array{headings: list<string>, rows: list<list<string|int|float>>, summary: array<string, string>, widths: list<int>} */
    private function finance(Carbon $from, Carbon $to): array
    {
        $in = app(FinanceStats::class)->inflows()->filter(fn ($r) => $r['at']->between($from, $to))
            ->groupBy(fn ($r) => $r['at']->format('Y-m'))->map(fn ($g) => (int) $g->sum('amount'));
        $out = VendorPayment::query()->where('status', VendorPaymentStatus::Completed)->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->get(['amount_sen', 'payment_date'])->groupBy(fn (VendorPayment $p) => $p->payment_date?->format('Y-m'))->map(fn ($g) => (int) $g->sum('amount_sen'));

        $months = $in->keys()->merge($out->keys())->unique()->sort()->values();
        $rows = $months->map(fn (string $ym) => [
            mb_substr(tarikh(Carbon::parse($ym.'-01')), 2),
            rm($in[$ym] ?? 0),
            rm($out[$ym] ?? 0),
            rm(($in[$ym] ?? 0) - ($out[$ym] ?? 0)),
        ])->all();

        $unpaid = Invoice::query()->where('status', '!=', InvoiceStatus::Paid);

        return [
            'headings' => ['Bulan', 'Kutipan', 'Bayaran Vendor', 'Bersih'],
            'rows' => $rows,
            'summary' => [
                'Jumlah kutipan' => rm((int) $in->sum()),
                'Jumlah bayaran vendor' => rm((int) $out->sum()),
                'Invois belum dijelaskan' => (clone $unpaid)->count().' ('.rm((int) (clone $unpaid)->selectRaw('COALESCE(SUM(total_sen - paid_sen),0) as b')->value('b')).')',
            ],
            'widths' => [16, 18, 18, 18],
        ];
    }

    /**
     * @param  Builder<Order>  $orders
     * @return array{headings: list<string>, rows: list<list<string|int|float>>, summary: array<string, string>, widths: list<int>}
     */
    private function certificates(Builder $orders): array
    {
        $list = (clone $orders)->with(['customer'])->withCount('certificates')->orderBy('order_no')->get();
        $rows = $list->map(fn (Order $o) => [
            $o->order_no,
            $o->customer->name,
            $o->quantity,
            (int) $o->certificates_count,
            match (true) {
                $o->stage->position() >= OrderStage::CertificatePosted->position() => 'Dipos',
                (int) $o->certificates_count > 0 => 'Dijana',
                default => 'Tertunggak',
            },
        ])->all();

        return [
            'headings' => ['No. Tempahan', 'Pelanggan', 'Peserta', 'Sijil Dijana', 'Status'],
            'rows' => $rows,
            'summary' => [
                'Sijil dijana' => (string) $list->sum('certificates_count'),
                'Tempahan tertunggak' => (string) count(array_filter($rows, fn (array $r) => $r[4] === 'Tertunggak')),
            ],
            'widths' => [20, 28, 10, 12, 12],
        ];
    }

    /**
     * @param  Builder<Order>  $orders
     * @return array{headings: list<string>, rows: list<list<string|int|float>>, summary: array<string, string>, widths: list<int>}
     */
    private function participants(Builder $orders): array
    {
        $list = (clone $orders)->get(['id', 'service', 'animal', 'quantity']);
        $rows = $list->groupBy(fn (Order $o) => $o->service->value.'|'.$o->animal->value)->map(fn ($g) => [
            $g->first()->service->label(),
            $g->first()->animal->label(),
            $g->count(),
            (int) $g->sum('quantity'),
        ])->sortByDesc(3)->values()->all();

        return [
            'headings' => ['Ibadah', 'Haiwan', 'Tempahan', 'Peserta / Bahagian'],
            'rows' => $rows,
            'summary' => ['Jumlah peserta' => (string) $list->sum('quantity')],
            'widths' => [16, 14, 12, 18],
        ];
    }
}
