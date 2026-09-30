<?php

namespace App\Http\Controllers;

use App\Enums\Module;
use App\Enums\PoStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\VendorPayment;
use App\Models\VendorReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Vendor module documents: PO receipt (A4 PDF) and private payment/report files. */
class VendorDocumentController
{
    public function purchaseOrder(Request $request, PurchaseOrder $po): PdfBuilder
    {
        Gate::authorize(Module::Vendors->viewPermission());
        $this->guardVendor($request->user(), $po->vendor_id);
        abort_if($request->user()?->isVendorPic() && $po->status === PoStatus::Draft, 403);

        $pdf = Pdf::view('pdf.purchase-order', ['po' => $po->load('vendor')])->format('a4');

        return $request->boolean('muat-turun') ? $pdf->download($po->po_no.'.pdf') : $pdf->inline($po->po_no.'.pdf');
    }

    /** Signed + authenticated; streams a payment receipt/advice or report file. */
    public function media(Request $request, Media $media): StreamedResponse
    {
        Gate::authorize(Module::Vendors->viewPermission());

        $owner = match ($media->model_type) {
            (new VendorPayment)->getMorphClass() => VendorPayment::query()->find($media->model_id),
            (new VendorReport)->getMorphClass() => VendorReport::query()->find($media->model_id),
            default => null,
        };

        abort_if($owner === null, 404);
        $this->guardVendor($request->user(), $owner->vendor_id);

        return response()->stream(function () use ($media) {
            $stream = $media->stream();
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $media->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($media->file_name).'"',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function guardVendor(?User $user, int $vendorId): void
    {
        abort_if($user?->isVendorPic() && $user->vendor_id !== $vendorId, 403);
    }
}
