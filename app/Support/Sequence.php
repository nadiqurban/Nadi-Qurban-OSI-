<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Gap-free counters for human-readable numbers, safe under concurrency:
 * the row is locked (SELECT … FOR UPDATE) inside a transaction.
 */
final class Sequence
{
    /** Next value of `$name`, starting at `$start` the first time. */
    public static function next(string $name, int $start = 1): int
    {
        return DB::transaction(function () use ($name, $start) {
            DB::table('sequences')->insertOrIgnore([
                'name' => $name,
                'next_value' => $start,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row = DB::table('sequences')->where('name', $name)->lockForUpdate()->first();
            $value = (int) $row->next_value;

            DB::table('sequences')->where('name', $name)->update([
                'next_value' => $value + 1,
                'updated_at' => now(),
            ]);

            return $value;
        });
    }

    /** The value next() would return, without consuming it (previews). */
    public static function peek(string $name, int $start = 1): int
    {
        return (int) (DB::table('sequences')->where('name', $name)->value('next_value') ?? $start);
    }
}
