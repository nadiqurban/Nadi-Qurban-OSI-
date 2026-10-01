<?php

namespace App\Listeners;

use App\Enums\Module;
use App\Enums\NotificationType;
use App\Enums\OrderStage;
use App\Events\InvoicePaid;
use App\Events\OrderStageChanged;
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
                $url, except: auth()->user()),
            OrderStage::PaymentVerified => Notifier::send(NotificationType::PaymentVerified, Module::Payments->viewPermission(),
                'Bayaran disahkan',
                'Bayaran '.rm($order->total_sen)." untuk {$order->order_no} telah disahkan.",
                $url, except: auth()->user()),
            OrderStage::ReportUploaded => Notifier::send(NotificationType::ExecutionReport, Module::Execution->viewPermission(),
                'Laporan pelaksanaan dihantar',
                "Bukti pelaksanaan {$order->order_no} ({$order->country->name}) menunggu pengesahan.",
                route('execution.index'), except: auth()->user()),
            default => null,
        };
    }

    public function handleInvoicePaid(InvoicePaid $event): void
    {
        $invoice = $event->invoice;

        Notifier::send(NotificationType::PaymentVerified, Module::Finance->viewPermission(),
            'Invois dijelaskan',
            "{$invoice->invoice_no} ({$invoice->customer_name}) telah dibayar sepenuhnya — ".rm($invoice->total_sen).'.',
            route('finance.invoice', $invoice), except: auth()->user());
    }
}
