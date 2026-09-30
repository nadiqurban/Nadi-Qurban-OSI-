<?php

namespace App\Actions\Pipeline;

use App\Actions\Orders\AdvanceStage;
use App\Enums\OrderStage;
use App\Enums\ReportStatus;
use App\Models\ExecutionReport;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "Hantar Laporan": vendor/HQ uploads photos + videos + notes → Menunggu Semakan HQ
 * (stage report_uploaded). Vendor PIC users may only report on their own orders.
 */
class SubmitExecutionReport
{
    public function __construct(private readonly AdvanceStage $stage) {}

    /**
     * @param  list<UploadedFile>  $images
     * @param  list<UploadedFile>  $videos
     */
    public function handle(Order $order, array $images, array $videos, ?string $notes, User $actor): ExecutionReport
    {
        return DB::transaction(function () use ($order, $images, $videos, $notes, $actor) {
            /** @var Order $order */
            $order = Order::query()->with(['allocation', 'executionReport.media'])->lockForUpdate()->findOrFail($order->id);

            if ($actor->isVendorPic() && $order->allocation?->vendor_id !== $actor->vendor_id) {
                abort(403);
            }

            if ($order->stage !== OrderStage::Executing) {
                throw ValidationException::withMessages(['order' => "Tempahan {$order->order_no} tidak menunggu laporan pelaksanaan."]);
            }

            $report = $order->executionReport ?? new ExecutionReport(['order_id' => $order->id]);
            $existing = $report->exists ? $report->media->count() : 0;

            if ($images === [] && $videos === [] && $existing === 0) {
                throw ValidationException::withMessages(['images' => 'Muat naik sekurang-kurangnya satu gambar atau video bukti pelaksanaan.']);
            }

            $report->fill([
                'vendor_id' => $order->allocation?->vendor_id,
                'status' => ReportStatus::Review,
                'notes' => $notes,
                'submitted_by' => $actor->id,
                'submitted_at' => now(),
            ])->save();

            foreach ($images as $file) {
                $report->addMedia($file)->toMediaCollection('images');
            }

            foreach ($videos as $file) {
                $report->addMedia($file)->toMediaCollection('videos');
            }

            $this->stage->handle($order, OrderStage::ReportUploaded, $actor, count($images).' gambar, '.count($videos).' video');

            return $report;
        });
    }
}
