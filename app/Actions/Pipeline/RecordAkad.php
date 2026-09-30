<?php

namespace App\Actions\Pipeline;

use App\Actions\Orders\AdvanceStage;
use App\Enums\AkadMethod;
use App\Enums\OrderStage;
use App\Models\AkadRecord;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "Sahkan Akad" / "Akad Pukal": records the wakalah akad (method, witness =
 * logged-in user, consent) and moves the order to akad_done.
 */
class RecordAkad
{
    public function __construct(private readonly AdvanceStage $stage) {}

    public function handle(Order $order, AkadMethod $method, bool $consented, User $witness): AkadRecord
    {
        if (! $consented) {
            throw ValidationException::withMessages(['consent' => 'Sila sahkan pelanggan telah faham & bersetuju dengan lafaz akad.']);
        }

        return DB::transaction(function () use ($order, $method, $witness) {
            /** @var Order $order */
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->stage !== OrderStage::PaymentVerified) {
                throw ValidationException::withMessages(['order' => "Tempahan {$order->order_no} tidak menunggu akad."]);
            }

            $record = AkadRecord::query()->create([
                'order_id' => $order->id,
                'method' => $method,
                'witness_id' => $witness->id,
                'consented' => true,
                'recorded_at' => now(),
            ]);

            $this->stage->handle($order, OrderStage::AkadDone, $witness, 'Akad melalui '.$method->label());

            return $record;
        });
    }
}
