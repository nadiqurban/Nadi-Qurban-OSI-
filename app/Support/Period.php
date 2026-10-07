<?php

namespace App\Support;

use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * "Tempoh" filter of Portal Ejen / Pengurusan Ejen: Semua · Hari · Bulan · Tahun · Julat Tarikh,
 * anchored on a reference date (or a from–to range).
 */
final class Period
{
    public const KEYS = ['all' => 'Semua', 'day' => 'Hari', 'month' => 'Bulan', 'year' => 'Tahun', 'range' => 'Julat Tarikh'];

    private const MONTHS = ['Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ogos', 'Sep', 'Okt', 'Nov', 'Dis'];

    public readonly string $key;

    public readonly Carbon $date;

    public readonly Carbon $from;

    public readonly Carbon $to;

    public function __construct(string $key = 'all', ?string $date = null, ?string $from = null, ?string $to = null)
    {
        $this->key = array_key_exists($key, self::KEYS) ? $key : 'all';
        $this->date = self::parse($date) ?? today();
        $from = self::parse($from) ?? $this->date->copy()->startOfMonth();
        $to = self::parse($to) ?? $this->date->copy();
        [$this->from, $this->to] = $from->lte($to) ? [$from, $to] : [$to, $from];
    }

    /** @return array{0: Carbon, 1: Carbon}|null inclusive bounds, null = no limit */
    public function bounds(): ?array
    {
        return match ($this->key) {
            'day' => [$this->date->copy()->startOfDay(), $this->date->copy()->endOfDay()],
            'month' => [$this->date->copy()->startOfMonth(), $this->date->copy()->endOfMonth()],
            'year' => [$this->date->copy()->startOfYear(), $this->date->copy()->endOfYear()],
            'range' => [$this->from->copy()->startOfDay(), $this->to->copy()->endOfDay()],
            default => null,
        };
    }

    /**
     * @template TQuery of QueryBuilder
     *
     * @param  TQuery  $query
     * @return TQuery
     */
    public function apply(QueryBuilder $query, string $column = 'created_at', bool $dateOnly = false): QueryBuilder
    {
        $bounds = $this->bounds();

        if ($bounds) {
            $query->whereBetween($column, $dateOnly ? [$bounds[0]->toDateString(), $bounds[1]->toDateString()] : $bounds);
        }

        return $query;
    }

    /** "Semua tempoh" · "Jun 2027" · "12 Jun 2027" · "01/06/2027 – 18/06/2027" */
    public function label(string $all = 'Semua tempoh'): string
    {
        $d = $this->date;

        return match ($this->key) {
            'day' => $d->day.' '.self::MONTHS[$d->month - 1].' '.$d->year,
            'month' => self::MONTHS[$d->month - 1].' '.$d->year,
            'year' => 'Tahun '.$d->year,
            'range' => $this->from->format('d/m/Y').' – '.$this->to->format('d/m/Y'),
            default => $all,
        };
    }

    /** File-name friendly: "Semua", "2027", "2027-06", "2027-06-12", "2027-06-01_2027-06-18". */
    public function slug(): string
    {
        return match ($this->key) {
            'day' => $this->date->toDateString(),
            'month' => $this->date->format('Y-m'),
            'year' => (string) $this->date->year,
            'range' => $this->from->toDateString().'_'.$this->to->toDateString(),
            default => 'Semua',
        };
    }

    private static function parse(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $value) ?: null;
        } catch (Throwable) {
            return null;
        }
    }
}
