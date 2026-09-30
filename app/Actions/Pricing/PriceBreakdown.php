<?php

namespace App\Actions\Pricing;

/**
 * Immutable price summary in sen: Harga (subtotal) − Diskaun = Jumlah; for
 * instalment plans Jumlah − Deposit = Baki Ansuran.
 */
final readonly class PriceBreakdown
{
    public function __construct(
        public int $unitPriceSen,
        public int $quantity,
        public int $subtotalSen,
        public int $discountSen,
        public int $totalSen,
        public int $depositSen = 0,
        public ?string $promoCode = null,
    ) {}

    public function balanceSen(): int
    {
        return max(0, $this->totalSen - $this->depositSen);
    }

    /**
     * Monthly instalments for the balance: equal floor amounts, with the leftover
     * sen added to the final month (PRD §6.10). Sum always equals the balance.
     *
     * @return list<int>
     */
    public function instalments(int $months): array
    {
        if ($months < 1) {
            return [];
        }

        $balance = $this->balanceSen();
        $monthly = intdiv($balance, $months);
        $schedule = array_fill(0, $months, $monthly);
        $schedule[$months - 1] += $balance - $monthly * $months;

        return $schedule;
    }

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'unit_price_sen' => $this->unitPriceSen,
            'quantity' => $this->quantity,
            'subtotal_sen' => $this->subtotalSen,
            'discount_sen' => $this->discountSen,
            'total_sen' => $this->totalSen,
            'deposit_sen' => $this->depositSen,
            'balance_sen' => $this->balanceSen(),
            'promo_code' => $this->promoCode,
        ];
    }
}
