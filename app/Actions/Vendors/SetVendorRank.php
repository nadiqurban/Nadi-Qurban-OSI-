<?php

namespace App\Actions\Vendors;

use App\Enums\RoleName;
use App\Enums\Severity;
use App\Enums\VendorLevel;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorRankHistory;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Prestasi tab: 1–10 ranking, Super Admin only; tier (level) follows the rank. */
class SetVendorRank
{
    public static function canChange(User $user): bool
    {
        return $user->hasRole(RoleName::SuperAdmin->value);
    }

    public function handle(Vendor $vendor, int $rank, User $actor): void
    {
        if (! self::canChange($actor)) {
            abort(403, 'Ranking hanya boleh diubah oleh Admin Sistem.');
        }

        if ($rank < 1 || $rank > 10) {
            throw ValidationException::withMessages(['rank' => 'Ranking mesti antara 1 hingga 10.']);
        }

        if ($vendor->rank === $rank) {
            return;
        }

        DB::transaction(function () use ($vendor, $rank, $actor) {
            $from = $vendor->rank;
            $vendor->forceFill(['rank' => $rank, 'level' => VendorLevel::fromRank($rank)])->save();

            VendorRankHistory::query()->create([
                'vendor_id' => $vendor->id,
                'from_rank' => $from,
                'to_rank' => $rank,
                'changed_by' => $actor->id,
                'created_at' => now(),
            ]);

            Audit::log('vendor.rank', "Ranking {$vendor->name} ".($from ?? '—')." → {$rank}", $vendor, Severity::Info,
                ['vendor_id' => $vendor->id, 'from' => $from, 'to' => $rank], $actor, 'vendors');
        });
    }
}
