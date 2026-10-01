<?php

namespace App\Http\Controllers;

use App\Enums\Module;
use App\Models\Certificate;
use App\Models\Document;
use App\Support\CertificateTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Dokumen: open/download a repository entry (private file or on-demand PDF). */
class DocumentController
{
    public function open(Request $request, Document $document): StreamedResponse|RedirectResponse
    {
        Gate::authorize(Module::Documents->viewPermission());
        $download = $request->boolean('muat-turun');

        if ($document->route_name) {
            return redirect()->route($document->route_name, ($document->route_params ?? []) + ($download ? ['muat-turun' => 1] : []));
        }

        $media = $document->file()->firstOrFail();

        return response()->stream(function () use ($media) {
            $stream = $media->stream();
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $media->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => ($download ? 'attachment' : 'inline').'; filename="'.addslashes($document->name).'"',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** One certificate as an A5 PDF (auto-registered "Sijil Qurban" entries). */
    public function certificate(Request $request, Certificate $certificate, CertificateTemplate $template): PdfBuilder
    {
        abort_unless($request->user()?->canAny([Module::Documents->viewPermission(), Module::Certificates->viewPermission(), Module::Orders->viewPermission()]), 403);

        $certificate->load('order.country');
        $pdf = Pdf::view('pdf.certificates', ['certificates' => [$template->render(CertificateTemplate::valuesFor($certificate))]])->format('a5');
        $name = $certificate->certificate_no.'.pdf';

        return $request->boolean('muat-turun') ? $pdf->download($name) : $pdf->inline($name);
    }
}
