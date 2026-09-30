<?php

namespace App\Navigation\Badges;

use App\Enums\OrderStatus;
use App\Models\Order;

/** Sidebar badge on "Tempahan & Pelanggan": orders Dalam Proses. */
class ActiveOrders
{
    public function __invoke(): int
    {
        return once(fn () => Order::query()->where('status', OrderStatus::InProgress)->count());
    }
}
