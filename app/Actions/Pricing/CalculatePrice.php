<?php

namespace App\Actions\Pricing;

use App\Models\Product;
use App\Models\PromoCode;
use InvalidArgumentException;

/**
 * The one place prices are calculated (orders, instalment plans, invoices):
 * unit price from the product × quantity − promo discount (− deposit), all in sen.
 */
class CalculatePrice
{
    public function handle(Product $product, int $quantity, ?PromoCode $promo = null, int $depositSen = 0): PriceBreakdown
    {
        return $this->fromUnitPrice($product->price_sen, $quantity, $promo, $depositSen);
    }

    public function fromUnitPrice(int $unitPriceSen, int $quantity, ?PromoCode $promo = null, int $depositSen = 0): PriceBreakdown
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Kuantiti mesti sekurang-kurangnya 1.');
        }

        if ($unitPriceSen < 0 || $depositSen < 0) {
            throw new InvalidArgumentException('Harga dan deposit tidak boleh negatif.');
        }

        $subtotal = $unitPriceSen * $quantity;
        $usable = $promo !== null && $promo->isUsable();
        $discount = $usable ? $promo->discountFor($subtotal) : 0;
        $total = $subtotal - $discount;

        return new PriceBreakdown(
            unitPriceSen: $unitPriceSen,
            quantity: $quantity,
            subtotalSen: $subtotal,
            discountSen: $discount,
            totalSen: $total,
            depositSen: min($depositSen, $total),
            promoCode: $usable ? $promo->code : null,
        );
    }
}
