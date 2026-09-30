<?php

namespace App\Actions\Vendors;

use App\Enums\PoStatus;
use App\Enums\Severity;
use App\Enums\VendorReportStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorReport;
use App\Support\Audit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Laporan tab: the vendor (or HQ) uploads evidence per animal for a PO →
 * Menunggu Semakan HQ (PO In Progress). HQ verifies → PO Completed, or asks
 * for a revision.
 */
class VendorReports
{
    public function __construct(private readonly PurchaseOrders $orders) {}

    /** @param  list<UploadedFile>  $files */
    public function submit(PurchaseOrder $po, array $files, string $animal, ?string $notes, User $actor): VendorReport
    {
        if ($actor->isVendorPic() && $actor->vendor_id !== $po->vendor_id) {
            abort(403);
        }

        if (! in_array($po->status, [PoStatus::Accepted, PoStatus::InProgress, PoStatus::Completed], true)) {
            throw ValidationException::withMessages(['files' => 'Laporan hanya boleh dimuat naik selepas PO diterima vendor.']);
        }

        if (! in_array($animal, Vendor::ANIMALS, true)) {
            throw ValidationException::withMessages(['animal' => 'Pilih jenis haiwan.']);
        }

        return DB::transaction(function () use ($po, $files, $animal, $notes, $actor) {
            $report = $po->report ?? new VendorReport(['purchase_order_id' => $po->id, 'vendor_id' => $po->vendor_id, 'status' => VendorReportStatus::Draft]);

            if ($report->status === VendorReportStatus::Verified) {
                throw ValidationException::withMessages(['files' => 'Laporan telah disahkan HQ.']);
            }

            if ($files === [] && (! $report->exists || $report->getMedia('files')->isEmpty())) {
                throw ValidationException::withMessages(['files' => 'Muat naik sekurang-kurangnya satu fail bukti.']);
            }

            $report->fill([
                'notes' => $notes ?? $report->notes,
                'status' => VendorReportStatus::Submitted,
                'revision_note' => null,
                'submitted_by' => $actor->id,
                'submitted_at' => now(),
            ])->save();

            foreach ($files as $file) {
                $report->addMedia($file)->withCustomProperties(['animal' => $animal])->toMediaCollection('files');
            }

            $this->orders->startProgress($po, $actor);

            Audit::log('report.submitted', 'Laporan '.$po->po_no.' dimuat naik ('.count($files).' fail '.$animal.')', $report, Severity::Info,
                ['vendor_id' => $po->vendor_id, 'ref' => $po->po_no], $actor, 'vendors');

            return $report;
        });
    }

    public function verify(VendorReport $report, User $actor): void
    {
        $this->guardHq($actor);

        if ($report->status !== VendorReportStatus::Submitted) {
            throw ValidationException::withMessages(['report' => 'Laporan tidak menunggu semakan.']);
        }

        DB::transaction(function () use ($report, $actor) {
            $report->forceFill(['status' => VendorReportStatus::Verified, 'verified_by' => $actor->id, 'verified_at' => now()])->save();
            $this->orders->complete($report->purchaseOrder, $actor);

            Audit::log('report.verified', "Laporan {$report->purchaseOrder->po_no} disahkan HQ — PO Completed", $report, Severity::Info,
                ['vendor_id' => $report->vendor_id, 'ref' => $report->purchaseOrder->po_no], $actor, 'vendors');
        });
    }

    public function requestRevision(VendorReport $report, string $note, User $actor): void
    {
        $this->guardHq($actor);

        if ($report->status !== VendorReportStatus::Submitted) {
            throw ValidationException::withMessages(['report' => 'Laporan tidak menunggu semakan.']);
        }

        $report->forceFill(['status' => VendorReportStatus::Revision, 'revision_note' => $note])->save();

        Audit::log('report.revision', "Semakan semula diminta untuk laporan {$report->purchaseOrder->po_no}", $report, Severity::Warning,
            ['vendor_id' => $report->vendor_id, 'ref' => $report->purchaseOrder->po_no, 'note' => $note], $actor, 'vendors');
    }

    private function guardHq(User $actor): void
    {
        if ($actor->isVendorPic()) {
            abort(403, 'Vendor tidak boleh mengesahkan laporan sendiri.');
        }
    }
}
