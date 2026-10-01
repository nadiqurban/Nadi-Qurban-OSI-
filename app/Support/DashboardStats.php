<?php

namespace App\Support;

use App\Enums\Animal;
use App\Enums\OrderStage;
use App\Enums\OrderStatus;
use App\Enums\VendorLevel;
use App\Enums\VendorStatus;
use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Dashboard Operasi figures. Everything is computed for the season
 * (settings season.year) up to the filter date, compared with the previous
 * season up to the same day. Cached 5 minutes; flush() on order/payment changes.
 * @phpstan-type DashboardData array{
 *     kpis: list<array{icon: string, tone: string, value: string, label: string, delta: string, trend: string}>,
 *     monthly: array{rows: list<array{label: string, value: float}>, target: float},
 *     gauge: array{pct: int, achieved: int, balance: int, target: int},
 *     countries: list<array{name: string, iso2: string, orders: int, sales: int, quantity: int, pct: int, color: string, lon: int, lat: int}>,
 *     vendors: list<array{name: string, country: string, pct: string, level: string, levelClasses: string}>,
 *     packages: list<array{name: string, count: int, width: int, color: string}>,
 *     animals: list<array{name: string, count: int, icon: string}>
 * }
 */
final class DashboardStats
{
    private const VERSION_KEY = 'dashboard.version';

    /** Map pin colours in share order (Dashboard Operasi.dc.html cData). */
    public const COUNTRY_COLORS = ['#42481c', '#5a6127', '#C9A227', '#8a924e', '#b1b67c', '#E0BE5A', '#d3d6ab'];

    /** Pin position per ISO2 (lon, lat). */
    public const COORDS = [
        'MY' => [102, 3], 'SA' => [45, 24], 'UG' => [32, 1], 'TD' => [19, 15], 'NG' => [8, 9], 'IN' => [78, 22],
        'BF' => [-2, 12], 'SO' => [46, 6], 'SD' => [30, 15], 'ID' => [113, -1], 'TH' => [101, 15], 'BD' => [90, 24],
        'KH' => [105, 12], 'PS' => [35, 32],
    ];

    /** Package bar colours (design pkgBreak). */
    public const PACKAGE_COLORS = ['#42481c', '#5a6127', '#8a924e', '#C9A227', '#d3d6ab'];

    private const MONTHS = ['Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ogo', 'Sep', 'Okt', 'Nov', 'Dis'];

    public function __construct(private readonly Settings $settings) {}

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, (int) Cache::get(self::VERSION_KEY, 1) + 1);
    }

    public function season(): int
    {
        return (int) $this->settings->get('season.year', now()->year);
    }

    public function targetSen(): int
    {
        return (int) $this->settings->get('season.target_rm', 5_000_000) * 100;
    }

    /** @return DashboardData */
    public function all(Carbon $until): array
    {
        $key = 'dashboard:'.Cache::get(self::VERSION_KEY, 1).':'.$this->season().':'.$until->toDateString();

        return Cache::remember($key, 300, fn () => $this->compute($until->copy()->endOfDay()));
    }

    /** @return DashboardData */
    private function compute(Carbon $until): array
    {
        $season = $this->season();

        return [
            'kpis' => $this->kpis($season, $until),
            'monthly' => $this->monthly($season, $until),
            'gauge' => $this->gauge($season, $until),
            'countries' => $this->countries($season, $until),
            'vendors' => $this->vendors(),
            'packages' => $this->packages($season, $until),
            'animals' => $this->animals($season, $until),
        ];
    }

    /** @return Builder<Order> */
    private function orders(int $season, Carbon $until): Builder
    {
        return Order::query()->where('orders.year', $season)->where('orders.status', '!=', OrderStatus::Cancelled)->where('orders.created_at', '<=', $until);
    }

    /** @return Builder<Order> */
    private function verified(int $season, Carbon $until): Builder
    {
        return $this->orders($season, $until)->whereIn('stage', self::stagesFrom(OrderStage::PaymentVerified));
    }

    /** @return list<string> */
    private static function stagesFrom(OrderStage $from, ?OrderStage $before = null): array
    {
        return array_values(array_map(fn (OrderStage $s) => $s->value, array_filter(OrderStage::cases(),
            fn (OrderStage $s) => $s->position() >= $from->position() && ($before === null || $s->position() < $before->position()))));
    }

    /**
     * 9 KPI cards with deltas against the previous season up to the same day.
     *
     * @return list<array{icon: string, tone: string, value: string, label: string, delta: string, trend: string}>
     */
    private function kpis(int $season, Carbon $until): array
    {
        $prevUntil = $until->copy()->subYear();
        $count = fn (int $y, Carbon $u) => $this->orders($y, $u)->count();
        $people = fn (int $y, Carbon $u) => (int) $this->orders($y, $u)->sum('quantity');
        $sales = fn (int $y, Carbon $u) => (int) $this->verified($y, $u)->sum('total_sen');
        $paidCount = fn (int $y, Carbon $u) => $this->verified($y, $u)->count();

        $avg = fn (int $y, Carbon $u) => $paidCount($y, $u) > 0 ? (int) round($sales($y, $u) / $paidCount($y, $u) / 100) * 100 : 0; // whole ringgit

        $pct = function (int $cur, int $prev): array {
            if ($prev <= 0) {
                return ['—', 'flat'];
            }
            $p = ($cur - $prev) / $prev * 100;

            return [($p >= 0 ? '↑ ' : '↓ ').number_format(abs($p), 1).'%', $p >= 0 ? 'up' : 'down'];
        };
        $week = fn (Builder $q) => (clone $q)->where('created_at', '>=', $until->copy()->subDays(7))->count();

        $base = Order::query()->where('year', $season)->where('status', '!=', OrderStatus::Cancelled);
        $unpaid = (clone $base)->where('status', OrderStatus::AwaitingPayment);
        $reports = (clone $base)->whereIn('stage', [OrderStage::VendorAssigned->value, OrderStage::Executing->value, OrderStage::ReportUploaded->value])
            ->whereDate('implementation_date', '<=', $until);
        $certs = (clone $base)->whereIn('stage', self::stagesFrom(OrderStage::ReportVerified, OrderStage::CertificatePosted));
        $upcoming = (clone $base)->whereBetween('implementation_date', [$until->copy()->startOfDay(), $until->copy()->addDays(30)]);

        $newVendors = Vendor::query()->where('created_at', '>=', $until->copy()->subDays(30))->count();
        $vendorCount = Vendor::query()->where('status', VendorStatus::Active)->count();

        $backlog = function (Builder $q) use ($week): array {
            $n = $week($q);

            return [$n > 0 ? '↑ '.$n : '—', $n > 0 ? 'warn' : 'flat'];
        };

        $rows = [
            ['shopping-cart-simple', 'primary', number_format($count($season, $until)), 'Jumlah Tempahan', $pct($count($season, $until), $count($season - 1, $prevUntil))],
            ['users-three', 'primary', number_format($people($season, $until)), 'Jumlah Peserta', $pct($people($season, $until), $people($season - 1, $prevUntil))],
            ['money', 'gold', rm_short($sales($season, $until)), 'Jumlah Jualan', $pct($sales($season, $until), $sales($season - 1, $prevUntil))],
            ['money', 'primary', rm($avg($season, $until)), 'Purata Nilai Tempahan', $pct($avg($season, $until), $avg($season - 1, $prevUntil))],
            ['truck', 'primary', number_format($vendorCount), 'Jumlah Vendor', [$newVendors > 0 ? '+'.$newVendors.' baharu' : '—', 'flat']],
            ['wallet', 'danger', number_format((clone $unpaid)->count()), 'Bayaran Tertunggak', $backlog($unpaid)],
            ['folders', 'warning', number_format((clone $reports)->count()), 'Laporan Tertunggak', $backlog($reports)],
            ['certificate', 'primary', number_format((clone $certs)->count()), 'Sijil Tertunggak', $backlog($certs)],
            ['calendar-check', 'info', number_format((clone $upcoming)->count()), 'Pelaksanaan Akan Datang', ['30 hari', 'info']],
        ];

        return array_map(fn ($r) => ['icon' => $r[0], 'tone' => $r[1], 'value' => $r[2], 'label' => $r[3], 'delta' => $r[4][0], 'trend' => $r[4][1]], $rows);
    }

    /**
     * Verified sales per month of the season (RM '000) + monthly target.
     *
     * @return array{rows: list<array{label: string, value: float}>, target: float}
     */
    private function monthly(int $season, Carbon $until): array
    {
        $byMonth = $this->verified($season, $until)->get(['created_at', 'total_sen'])
            ->groupBy(fn (Order $o) => (int) $o->created_at->format('n'))->map(fn ($g) => (int) $g->sum('total_sen'));

        return [
            'rows' => array_map(fn (int $m) => ['label' => self::MONTHS[$m - 1], 'value' => round(($byMonth[$m] ?? 0) / 100 / 1000, 1)], range(1, 12)),
            'target' => round($this->targetSen() / 12 / 100 / 1000, 1),
        ];
    }

    /** @return array{pct: int, achieved: int, balance: int, target: int} sen */
    private function gauge(int $season, Carbon $until): array
    {
        $achieved = (int) $this->verified($season, $until)->sum('total_sen');
        $target = $this->targetSen();

        return [
            'pct' => $target > 0 ? (int) min(100, round($achieved / $target * 100)) : 0,
            'achieved' => $achieved,
            'balance' => max(0, $target - $achieved),
            'target' => $target,
        ];
    }

    /** @return list<array{name: string, iso2: string, orders: int, sales: int, quantity: int, pct: int, color: string, lon: int, lat: int}> */
    private function countries(int $season, Carbon $until): array
    {
        $rows = $this->orders($season, $until)->join('countries', 'countries.id', '=', 'orders.country_id')
            ->selectRaw('countries.name, countries.iso2, COUNT(*) as n, SUM(orders.total_sen) as sales, SUM(orders.quantity) as qty')
            ->groupBy('countries.name', 'countries.iso2')->orderByDesc('n')->limit(7)->get();
        $total = max(1, $this->orders($season, $until)->count());

        return $rows->values()->map(fn ($r, int $i) => [
            'name' => (string) $r->getAttribute('name'),
            'iso2' => (string) $r->getAttribute('iso2'),
            'orders' => (int) $r->getAttribute('n'),
            'sales' => (int) $r->getAttribute('sales'),
            'quantity' => (int) $r->getAttribute('qty'),
            'pct' => (int) round((int) $r->getAttribute('n') / $total * 100),
            'color' => self::COUNTRY_COLORS[$i] ?? '#d3d6ab',
            'lon' => self::COORDS[(string) $r->getAttribute('iso2')][0] ?? 0,
            'lat' => self::COORDS[(string) $r->getAttribute('iso2')][1] ?? 0,
        ])->all();
    }

    /** @return list<array{name: string, country: string, pct: string, level: string, levelClasses: string}> */
    private function vendors(): array
    {
        $labels = ['platinum' => 'Platinum', 'gold' => 'Emas', 'silver' => 'Perak', 'bronze' => 'Gangsa'];

        return Vendor::query()->withPoStats()->with('country')->where('status', VendorStatus::Active)->get()
            ->sortByDesc(fn (Vendor $v) => [(int) $v->counted_po_count > 0 ? $v->completed_po_count / $v->counted_po_count : -1, (int) $v->rank])
            ->take(5)->values()
            ->map(function (Vendor $v) use ($labels) {
                $level = $v->level ?? VendorLevel::fromRank((int) $v->rank);

                return [
                    'name' => $v->name,
                    'country' => $v->country->name,
                    'pct' => $v->completionLabel(),
                    'level' => $labels[$level->value],
                    'levelClasses' => $level->badgeClasses(),
                ];
            })->all();
    }

    /** @return list<array{name: string, count: int, width: int, color: string}> */
    private function packages(int $season, Carbon $until): array
    {
        $rows = $this->orders($season, $until)->selectRaw('package_name, COUNT(*) as n')->groupBy('package_name')->orderByDesc('n')->limit(5)
            ->pluck('n', 'package_name')->map(fn ($n) => (int) $n);
        $max = max(1, (int) $rows->max());

        return $rows->keys()->values()->map(fn (string $name, int $i) => [
            'name' => $name,
            'count' => $rows[$name],
            'width' => (int) round($rows[$name] / $max * 100),
            'color' => self::PACKAGE_COLORS[$i] ?? '#d3d6ab',
        ])->all();
    }

    /** @return list<array{name: string, count: int, icon: string}> */
    private function animals(int $season, Carbon $until): array
    {
        $rows = $this->orders($season, $until)->selectRaw('animal, COUNT(*) as n')->groupBy('animal')->pluck('n', 'animal');
        $icons = ['lembu' => 'cow', 'kambing' => 'dog', 'unta' => 'horse'];

        return collect(Animal::cases())->map(fn ($a) => [
            'name' => $a->label(),
            'count' => (int) ($rows[$a->value] ?? 0),
            'icon' => $icons[$a->value],
        ])->all();
    }
}
