<?php

namespace App\Livewire\Public;

use App\Actions\Booking\StartBookingPayment;
use App\Actions\Installments\ProcessChipPurchase;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PaymentGatewayTransaction;
use App\Services\Chip\ChipGateway;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

/**
 * Tempahan Awam — done screen: payment banner + A4 "Resit Bayaran" (print / PDF).
 * Back from CHIP with ?tx= the purchase is re-checked (idempotent with the webhook).
 * The secret order tracking token is the only key, as for the tracking link.
 */
#[Layout('layouts::booking')]
#[Title('Resit Bayaran')]
class BookingReceipt extends Component
{
    #[Locked]
    public string $token = '';

    public function mount(string $token, ChipGateway $chip, ProcessChipPurchase $process): void
    {
        $this->token = $token;
        $order = $this->order();
        $ref = request()->query('tx');

        if (! is_string($ref) || $ref === '' || $order->status !== OrderStatus::AwaitingPayment) {
            return;
        }

        $tx = PaymentGatewayTransaction::query()->where('order_id', $order->id)->where('reference', $ref)->first();

        if ($tx && ! $tx->isPaid() && $tx->purchase_id && $chip->isConfigured()) {
            try {
                $process->handle($chip->getPurchase($tx->purchase_id));
            } catch (RuntimeException) {
                // The webhook will reconcile; show the current state.
            }
        }
    }

    private function order(): Order
    {
        /** @var Order */
        return Order::query()->with(['customer', 'country', 'participants', 'payment', 'agent.user'])
            ->where('source', 'public')->where('tracking_token', $this->token)->firstOrFail();
    }

    public function payAgain(StartBookingPayment $start): mixed
    {
        try {
            $tx = $start->handle($this->order());
        } catch (ValidationException $e) {
            $this->addError('pay', (string) collect($e->errors())->flatten()->first());

            return null;
        }

        return $this->redirect((string) $tx->checkout_url);
    }

    public function render(): mixed
    {
        $order = $this->order();
        $paid = $order->payment?->status === PaymentStatus::Verified;
        $awaiting = $order->status === OrderStatus::AwaitingPayment;

        return view('livewire.public.booking-receipt', [
            'order' => $order,
            'paid' => $paid,
            'awaiting' => $awaiting,
            'banner' => match (true) {
                $paid => ['Bayaran Berjaya', "Terima kasih, {$order->customer->name}. Tempahan anda telah diterima dan disahkan melalui CHIP.", 'success'],
                $awaiting => ['Bayaran Belum Selesai', "Tempahan {$order->order_no} disimpan tetapi bayaran belum diterima. Sila cuba bayar semula.", 'warning'],
                $order->payment?->status === PaymentStatus::Rejected => ['Bukti Bayaran Ditolak', 'Sila hubungi Nadi Qurban untuk bantuan.', 'danger'],
                default => ['Bukti Bayaran Diterima', "Terima kasih, {$order->customer->name}. Bukti bayaran ({$order->payment_method->label()}) telah dihantar dan akan disahkan oleh pihak HQ.", 'success'],
            },
            'againUrl' => $order->agent ? $order->agent->shareUrl() : route('booking'),
        ]);
    }
}
