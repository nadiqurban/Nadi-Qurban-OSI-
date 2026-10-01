<?php

namespace App\Listeners;

use App\Enums\Module;
use App\Enums\NotificationType;
use App\Enums\OrderStage;
use App\Events\InvoicePaid;
use App\Events\OrderStageChanged;
use App\Models\User;
use App\Support\Notifier;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/** Notifikasi triggers for order pipeline + finance events (after the DB commit). */
class SendPipelineNotifications implements ShouldHandleEventsAfterCommit
{
    public function handleStage(OrderStageChanged $event): void
    {
        $order = $event->order->loadMissing('customer', 'country');
        $url = route('orders.show', $order);

        match ($event->to) {
            OrderStage::Received => Notifier::send(NotificationType::NewOrder, Module::Orders->viewPermission(),
                'Tempahan baharu diterima',
                "{$order->order_no} daripada {$order->customer->name} — {$order->service->label()} {$order->animal->label()} ({$order->country->name}).",
                $url, except: $this->actor()),
            OrderStage::PaymentVerified => Notifier::send(NotificationType::PaymentVerified, Module::Payments->viewPermission(),
                'Bayaran disahkan',
                'Bayaran '.rm($order->total_sen)." untuk {$order->order_no} telah disahkan.",
                $url, except: $this->actor()),
            OrderStage::ReportUploaded => Notifier::send(NotificationType::ExecutionReport, Module::Execution->viewPermission(),
                'Laporan pelaksanaan dihantar',
                "Bukti pelaksanaan {$order->order_no} ({$order->country->name}) menunggu pengesahan.",
                route('execution.index'), except: $this->actor()),
            default => null,
        };
    }

    public function handleInvoicePaid(InvoicePaid $event): void
    {
        $invoice = $event->invoice;

        Notifier::send(NotificationType::PaymentVerified, Module::Finance->viewPermission(),
            'Invois dijelaskan',
            "{$invoice->invoice_no} ({$invoice->customer_name}) telah dibayar sepenuhnya — ".rm($invoice->total_sen).'.',
            route('finance.invoice', $invoice), except: $this->actor());
    }

    /** Staff user behind the change (null for API clients, webhooks and the scheduler). */
    private function actor(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
