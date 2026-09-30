<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Login brand panel statistics (PRD §6.1): active countries, active vendors,
 * participants in the current season. Source tables arrive in later phases
 * (countries: Phase 2, vendors: Phase 6, order_participants: Phase 3); until
 * then the design's figures are shown.
 */
class BrandStats
{
    public function __construct(private readonly Settings $settings) {}

    /** @return list<array{value: string, label: string}> */
    public function all(): array
    {
        return Cache::remember('brand-stats', now()->addMinutes(10), function () {
            $season = (int) $this->settings->get('season.year', 2027);

            return [
                ['value' => number_format($this->implementationCountries() ?? 7), 'label' => 'Negara Pelaksanaan'],
                ['value' => number_format($this->count('vendors', ['status' => 'aktif']) ?? 42), 'label' => 'Rakan Vendor'],
                ['value' => number_format($this->participants($season) ?? 6540), 'label' => 'Peserta '.$season],
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $where
     */
    private function count(string $table, array $where): ?int
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        $query = DB::table($table);

        foreach ($where as $column => $value) {
            if (Schema::hasColumn($table, $column)) {
                $query->where($column, $value);
            }
        }

        return $query->count();
    }

    /** Countries that have at least one active product. */
    private function implementationCountries(): ?int
    {
        if (! Schema::hasTable('products')) {
            return null;
        }

        return DB::table('products')->whereNull('deleted_at')->where('is_active', true)->distinct()->count('country_id');
    }

    private function participants(int $season): ?int
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'year')) {
            return null;
        }

        return (int) DB::table('orders')->where('year', $season)->sum('quantity');
    }
}
