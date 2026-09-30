<?php

namespace App\Actions\Orders;

use App\Enums\PaymentStatus;
use App\Enums\Severity;
use App\Models\Order;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/** Order detail edits: customer contact/address, implementation date/year, participants, payment proof. */
class UpdateOrderDetails
{
    /**
     * @param  array{name: string, phone: string, email: ?string, address: ?string, postcode: ?string, city: ?string, state: ?string}  $customer
     * @param  array{year: int, implementation_date: ?string, notes: ?string}  $orderData
     */
    public function details(Order $order, array $customer, array $orderData, User $actor): void
    {
        DB::transaction(function () use ($order, $customer, $orderData, $actor) {
            $order->customer->fill($customer)->save();
            $order->fill($orderData)->save();

            Audit::log('order.updated', "Butiran {$order->order_no} dikemaskini", $order, Severity::Info, [
                'fields' => array_keys($order->getChanges() + $order->customer->getChanges()),
            ], $actor, 'orders');
        });
    }

    /** @param  array<int, string|null>  $names  position => name */
    public function participants(Order $order, array $names, User $actor): void
    {
        DB::transaction(function () use ($order, $names, $actor) {
            $order->participants()->delete();

            $position = 0;
            foreach ($names as $name) {
                $order->participants()->create(['position' => ++$position, 'name' => trim((string) $name) ?: null]);
            }

            Audit::log('order.participants', "Senarai peserta {$order->order_no} dikemaskini", $order, Severity::Info, ['count' => $position], $actor, 'orders');
        });
    }

    public function proof(Order $order, UploadedFile $file, User $actor): void
    {
        $payment = $order->payment ?? $order->payments()->create([
            'method' => $order->payment_method,
            'amount_sen' => $order->total_sen,
            'status' => PaymentStatus::Pending,
        ]);

        $payment->addMedia($file)->usingFileName('bukti-'.$order->order_no.'.'.$file->extension())->toMediaCollection('proof');
        $payment->forceFill(['paid_at' => $payment->paid_at ?? now()])->save();

        Audit::log('payment.proof', "Bukti bayaran {$order->order_no} dimuat naik", $order, Severity::Info, [], $actor, 'payments');
    }
}
