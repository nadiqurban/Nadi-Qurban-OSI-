<?php

namespace App\Actions\Pipeline;

use App\Actions\Orders\AdvanceStage;
use App\Enums\OrderStage;
use App\Enums\ReportStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** HQ "Sahkan Selesai": report verified → report_verified → final_report (ready for AWB). */
class VerifyExecutionReport
{
    public function __construct(private readonly AdvanceStage $stage) {}

    public function handle(Order $order, User $actor): void
    {
        if ($actor->isVendorPic()) {
            abort(403, 'Vendor tidak boleh mengesahkan laporan sendiri.');
        }

        DB::transaction(function () use ($order, $actor) {
            /** @var Order $order */
            $order = Order::query()->with('executionReport')->lockForUpdate()->findOrFail($order->id);

            if ($order->stage !== OrderStage::ReportUploaded || ! $order->executionReport) {
                throw ValidationException::withMessages(['order' => "Laporan {$order->order_no} tidak menunggu semakan."]);
            }

            $order->executionReport->forceFill([
                'status' => ReportStatus::Verified,
                'verified_by' => $actor->id,
                'verified_at' => now(),
            ])->save();

            $this->stage->handle($order, OrderStage::ReportVerified, $actor);
            $this->stage->handle($order, OrderStage::FinalReport, $actor);
        });
    }
}
