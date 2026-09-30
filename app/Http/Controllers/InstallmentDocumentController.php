<?php

namespace App\Http\Controllers;

use App\Enums\Module;
use App\Models\InstallmentPlan;
use App\Models\PaymentGatewayTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

/** Instalment receipts: A4 for HQ (Bayaran Ansuran) and A5 for the public portal. */
class InstallmentDocumentController
{
    public function receipt(Request $request, InstallmentPlan $plan): PdfBuilder
    {
        Gate::authorize(Module::Installments->viewPermission());

        $plan->load(['customer', 'installments']);
        $pdf = Pdf::view('pdf.installment-receipt', ['plan' => $plan, 'size' => 'A4'])->format('a4');
        $name = 'Resit-'.$plan->order_no.'.pdf';

        return $request->boolean('muat-turun') ? $pdf->download($name) : $pdf->inline($name);
    }

    /** Public: the pay token + transaction reference identify the receipt. */
    public function portalReceipt(Request $request, string $token, string $reference): PdfBuilder
    {
        $plan = InstallmentPlan::query()->where('pay_token', $token)->with(['customer', 'installments'])->firstOrFail();
        $tx = PaymentGatewayTransaction::query()->where('installment_plan_id', $plan->id)
            ->where('reference', $reference)->where('status', PaymentGatewayTransaction::PAID)->firstOrFail();

        $pdf = Pdf::view('pdf.installment-receipt', ['plan' => $plan, 'tx' => $tx, 'size' => 'A5'])->format('a5');

        return $request->boolean('muat-turun') ? $pdf->download('Resit-'.$tx->reference.'.pdf') : $pdf->inline('Resit-'.$tx->reference.'.pdf');
    }
}
