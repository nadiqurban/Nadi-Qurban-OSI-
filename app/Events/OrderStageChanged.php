<?php

namespace App\Events;

use App\Enums\OrderStage;
use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Fired after every pipeline transition (notifications & outgoing webhooks listen here). */
class OrderStageChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public ?OrderStage $from,
        public OrderStage $to,
    ) {}
}
