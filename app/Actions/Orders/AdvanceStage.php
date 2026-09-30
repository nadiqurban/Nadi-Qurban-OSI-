<?php

namespace App\Actions\Orders;

use App\Enums\OrderStage;
use App\Enums\Severity;
use App\Events\OrderStageChanged;
use App\Models\Order;
use App\Models\OrderStageHistory;
use App\Models\User;
use App\Support\Audit;
use InvalidArgumentException;

/**
 * Moves an order forward in the pipeline: writes order_stage_histories, the audit
 * log and fires OrderStageChanged. Call inside the caller's DB transaction.
 * Stages never move backwards (a cancel is a separate, explicit action).
 */
class AdvanceStage
{
    public function handle(Order $order, OrderStage $to, ?User $actor, ?string $note = null): void
    {
        $from = $order->stage;

        $isFirst = ! $order->stageHistories()->exists();

        if (! $isFirst && ! $to->isAfter($from)) {
            throw new InvalidArgumentException("Tempahan {$order->order_no} sudah berada di peringkat {$from->label()}.");
        }

        $order->forceFill(['stage' => $to])->save();

        OrderStageHistory::query()->create([
            'order_id' => $order->id,
            'stage' => $to,
            'user_id' => $actor?->id,
            'note' => $note,
            'created_at' => now(),
        ]);

        Audit::log('order.stage', "{$order->order_no}: {$to->label()}", $order, Severity::Info, [
            'from' => $from->value,
            'to' => $to->value,
        ], $actor, 'orders');

        OrderStageChanged::dispatch($order, $from, $to);
    }
}
