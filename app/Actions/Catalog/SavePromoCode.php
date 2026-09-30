<?php

namespace App\Actions\Catalog;

use App\Enums\Severity;
use App\Models\PromoCode;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Str;

class SavePromoCode
{
    /**
     * @param  array{code: string, description: ?string, type: string, value: int, usage_limit: ?int, expires_at: ?string, is_active: bool}  $data
     */
    public function handle(?PromoCode $promo, array $data, User $actor): PromoCode
    {
        $isNew = $promo === null;
        $promo ??= new PromoCode;
        $before = $isNew ? [] : $promo->only(['code', 'type', 'value', 'usage_limit', 'expires_at', 'is_active']);

        $promo->fill(['code' => mb_strtoupper(trim($data['code']))] + $data)->save();

        Audit::log(
            $isNew ? 'promo.created' : 'promo.updated',
            $isNew ? "Kod promosi {$promo->code} dicipta" : "Kod promosi {$promo->code} dikemaskini",
            $promo,
            Severity::Warning,
            $isNew ? ['discount' => $promo->discountLabel()] : ['before' => $before],
            $actor,
            'catalog',
        );

        return $promo;
    }

    /** "NQ" + 6 unambiguous characters (Kod Promosi.dc.html autoCode). */
    public static function generateCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = 'NQ';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (PromoCode::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    /** Case-insensitive, alphanumeric codes only. */
    public static function normalise(string $code): string
    {
        return Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
    }
}
