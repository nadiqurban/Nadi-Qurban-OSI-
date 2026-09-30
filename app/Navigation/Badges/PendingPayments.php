<?php

namespace App\Navigation\Badges;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;

/** Sidebar badge on "Pengesahan Bayaran": payments waiting for verification. */
class PendingPayments
{
    public function __invoke(): int
    {
        return once(fn () => Order::query()
            ->where('status', OrderStatus::Accepted)
            ->whereHas('payment', fn ($q) => $q->where('status', PaymentStatus::Pending))
            ->count());
    }
}
