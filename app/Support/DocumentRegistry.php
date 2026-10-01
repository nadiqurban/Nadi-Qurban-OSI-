<?php

namespace App\Support;

use App\Enums\DocumentCategory;
use App\Enums\Service;
use App\Models\Certificate;
use App\Models\Document;
use App\Models\ExecutionReport;
use App\Models\InstallmentPlan;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\ReportExport;
use App\Models\VendorPayment;
use App\Models\VendorReport;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Auto-registers system files into Dokumen: media uploaded anywhere in the app
 * (payment proofs, PO receipts/advice, execution photos/videos, vendor reports)
 * and PDFs rendered on demand (certificates, invoices, quotations, report exports).
 * Idempotent through the unique `key`.
 */
final class DocumentRegistry
{
    public function media(Media $media): ?Document
    {
        $map = [
            (new VendorPayment)->getMorphClass() => DocumentCategory::PaymentReceipt,
            (new ExecutionReport)->getMorphClass() => DocumentCategory::Media,
            (new VendorReport)->getMorphClass() => DocumentCategory::Report,
            (new Payment)->getMorphClass() => DocumentCategory::Invoice,
            (new InstallmentPlan)->getMorphClass() => DocumentCategory::Invoice,
        ];
        $category = $map[$media->model_type] ?? null;

        if ($category === null) {
            return null;
        }

        $service = Document::serviceFromName($media->file_name);
        if ($service === 'Lain' && $media->model_type === (new ExecutionReport)->getMorphClass()) {
            $service = ExecutionReport::query()->with('order')->find($media->model_id)?->order?->animal->label() ?? 'Lain';
        }

        return $this->register("media:{$media->id}", [
            'name' => $media->file_name,
            'category' => $category,
            'service' => $service,
            'extension' => strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION) ?: 'bin'),
            'size' => (int) $media->size,
            'media_id' => $media->id,
            'created_at' => $media->created_at,
        ]);
    }

    public function certificate(Certificate $certificate): Document
    {
        $order = $certificate->relationLoaded('order') ? $certificate->order : Order::query()->find($certificate->order_id);

        return $this->register("certificate:{$certificate->id}", [
            'name' => 'Sijil '.($order?->service->label() ?? 'Qurban').' '.$certificate->certificate_no.'.pdf',
            'category' => DocumentCategory::Certificate,
            'service' => $order ? self::serviceOf($order) : 'Lain',
            'extension' => 'pdf',
            'route_name' => 'documents.certificate',
            'route_params' => ['certificate' => $certificate->id],
            'created_at' => $certificate->generated_at ?? now(),
        ]);
    }

    public function invoice(Invoice $invoice): Document
    {
        return $this->register("invoice:{$invoice->id}", [
            'name' => 'Invois '.$invoice->invoice_no.'.pdf',
            'category' => DocumentCategory::Invoice,
            'service' => 'Lain',
            'extension' => 'pdf',
            'route_name' => 'finance.invoice.pdf',
            'route_params' => ['invoice' => $invoice->id],
            'created_at' => $invoice->created_at,
        ]);
    }

    public function quotation(Quotation $quotation): Document
    {
        return $this->register("quotation:{$quotation->id}", [
            'name' => 'Quotation '.$quotation->quotation_no.'.pdf',
            'category' => DocumentCategory::Invoice,
            'service' => 'Lain',
            'extension' => 'pdf',
            'route_name' => 'finance.quotation',
            'route_params' => ['quotation' => $quotation->id],
            'created_at' => $quotation->created_at,
        ]);
    }

    public function report(ReportExport $report): Document
    {
        return $this->register("report:{$report->id}", [
            'name' => $report->file_name,
            'category' => DocumentCategory::Report,
            'service' => 'Lain',
            'extension' => $report->format,
            'size' => $report->size,
            'route_name' => 'reports.download',
            'route_params' => ['report' => $report->id],
            'created_at' => $report->completed_at ?? now(),
        ]);
    }

    /** Backfill everything that already exists (seeders, first deploy). */
    public function sync(): int
    {
        $before = Document::query()->count();

        Media::query()->where('model_type', '!=', (new Document)->getMorphClass())->each(fn (Media $m) => $this->media($m));
        Certificate::query()->with('order')->each(fn (Certificate $c) => $this->certificate($c));
        Invoice::query()->each(fn (Invoice $i) => $this->invoice($i));
        Quotation::query()->each(fn (Quotation $q) => $this->quotation($q));

        return Document::query()->count() - $before;
    }

    public static function serviceOf(Order $order): string
    {
        return $order->service === Service::Aqiqah ? 'Aqiqah' : $order->animal->label();
    }

    /** @param array<string, mixed> $attributes */
    private function register(string $key, array $attributes): Document
    {
        $doc = Document::withTrashed()->firstOrNew(['key' => $key]);

        if ($doc->exists) {
            return $doc;   // registered before (and possibly deleted by a user on purpose)
        }

        $doc->forceFill($attributes + ['source' => 'system'])->save();

        return $doc;
    }
}
