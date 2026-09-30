<?php

use App\Actions\Pricing\CalculatePrice;
use App\Enums\DiscountType;
use App\Models\PromoCode;

function promo(DiscountType $type, int $value, array $attrs = []): PromoCode
{
    return new PromoCode(array_merge([
        'code' => 'TEST', 'type' => $type, 'value' => $value, 'is_active' => true,
        'used_count' => 0, 'usage_limit' => null, 'expires_at' => null,
    ], $attrs));
}

it('calculates percent discounts on the subtotal', function () {
    expect(promo(DiscountType::Percent, 10)->discountFor(350000))->toBe(35000)
        ->and(promo(DiscountType::Percent, 15)->discountFor(85000))->toBe(12750)
        ->and(promo(DiscountType::Percent, 8)->discountFor(99999))->toBe(7999); // floors to the sen
});

it('applies a fixed RM discount as an amount, not a percentage (prototype bug)', function () {
    // "RM50" = 5000 sen off, NOT 50%.
    expect(promo(DiscountType::Fixed, 5000)->discountFor(350000))->toBe(5000)
        ->and(promo(DiscountType::Fixed, 5000)->discountFor(3000))->toBe(3000); // capped at subtotal
});

it('knows when a code is usable', function () {
    expect(promo(DiscountType::Percent, 10)->isUsable())->toBeTrue()
        ->and(promo(DiscountType::Percent, 10, ['is_active' => false])->isUsable())->toBeFalse()
        ->and(promo(DiscountType::Percent, 10, ['expires_at' => now()->subDay()])->isUsable())->toBeFalse()
        ->and(promo(DiscountType::Percent, 10, ['expires_at' => today()])->isUsable())->toBeTrue()
        ->and(promo(DiscountType::Percent, 10, ['usage_limit' => 5, 'used_count' => 5])->isUsable())->toBeFalse()
        ->and(promo(DiscountType::Percent, 10, ['usage_limit' => 5, 'used_count' => 4])->isUsable())->toBeTrue();
});

it('labels discounts like the design', function () {
    expect(promo(DiscountType::Percent, 10)->discountLabel())->toBe('10%')
        ->and(promo(DiscountType::Fixed, 5000)->discountLabel())->toBe('RM 50');
});

it('calculates unit price × quantity − promo', function () {
    $b = (new CalculatePrice)->fromUnitPrice(350000, 7, promo(DiscountType::Percent, 10));

    expect($b->subtotalSen)->toBe(2450000)
        ->and($b->discountSen)->toBe(245000)
        ->and($b->totalSen)->toBe(2205000)
        ->and($b->promoCode)->toBe('TEST');
});

it('ignores unusable promo codes', function () {
    $b = (new CalculatePrice)->fromUnitPrice(85000, 1, promo(DiscountType::Percent, 50, ['is_active' => false]));

    expect($b->discountSen)->toBe(0)->and($b->totalSen)->toBe(85000)->and($b->promoCode)->toBeNull();
});

it('deducts the deposit and splits instalments with the remainder in the last month', function () {
    $b = (new CalculatePrice)->fromUnitPrice(350000, 1, promo(DiscountType::Fixed, 5000), depositSen: 50000);

    expect($b->totalSen)->toBe(345000)
        ->and($b->balanceSen())->toBe(295000);

    $six = $b->instalments(6);
    expect($six)->toHaveCount(6)
        ->and(array_sum($six))->toBe(295000)
        ->and($six[0])->toBe(49100)
        ->and($six[5])->toBe(49500);

    expect((new CalculatePrice)->fromUnitPrice(350000, 1)->instalments(6))->toBe([58300, 58300, 58300, 58300, 58300, 58500]);

    foreach ([3, 9, 12] as $months) {
        expect(array_sum($b->instalments($months)))->toBe(295000);
    }
});

it('rejects a zero quantity', function () {
    (new CalculatePrice)->fromUnitPrice(1000, 0);
})->throws(InvalidArgumentException::class);

it('parses ringgit input into sen', function (string $input, ?int $sen) {
    expect(parse_rm($input))->toBe($sen);
})->with([
    ['3500', 350000],
    ['3,500.50', 350050],
    ['RM 850', 85000],
    ['12.5', 1250],
    ['abc', null],
    ['1.234', null],
    ['', null],
]);
