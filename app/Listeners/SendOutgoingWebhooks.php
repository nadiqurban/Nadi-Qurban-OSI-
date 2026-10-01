<?php

namespace App\Listeners;

use App\Enums\OrderStage;
use App\Events\OrderStageChanged;
use App\Http\Controllers\Api\V1\OrderController;
use App\Support\Webhooks;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/** Pipeline stages → outgoing webhook events (payment.confirmed, report.verified, awb.generated, order.completed). */
class SendOutgoingWebhooks implements ShouldHandleEventsAfterCommit
{
    public function __construct(private readonly Webhooks $webhooks) {}

    public function handle(OrderStageChanged $event): void
    {
        $name = match ($event->to) {
            OrderStage::PaymentVerified => 'payment.confirmed',
            OrderStage::ReportVerified => 'report.verified',
            OrderStage::AwbGenerated => 'awb.generated',
            OrderStage::Completed => 'order.completed',
            default => null,
        };

        if ($name !== null) {
            $order = $event->order->loadMissing(['customer', 'country', 'participants', 'payment']);
            $this->webhooks->dispatch($name, ['order' => OrderController::present($order)]);
        }
    }
}
