<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

/** Kewangan A4 PDFs (route group requires finance.view). */
class FinanceDocumentController
{
    public function invoice(Request $request, Invoice $invoice): PdfBuilder
    {
        return $this->render($request, $invoice->load('items')->toDocument());
    }

    public function quotation(Request $request, Quotation $quotation): PdfBuilder
    {
        return $this->render($request, $quotation->toDocument());
    }

    private function render(Request $request, object $doc): PdfBuilder
    {
        $pdf = Pdf::view('pdf.finance-doc', ['doc' => $doc])->format('a4');

        return $request->boolean('muat-turun') ? $pdf->download($doc->number.'.pdf') : $pdf->inline($doc->number.'.pdf');
    }
}
