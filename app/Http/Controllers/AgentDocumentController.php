<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Support\AgentStats;
use App\Support\Period;
use Illuminate\Http\Request;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

/** Pengurusan Ejen: Invois Komisen A4 for the chosen period (route requires agents.view). */
class AgentDocumentController
{
    public function commissionInvoice(Request $request): PdfBuilder
    {
        $period = new Period(
            (string) $request->query('tempoh', 'all'),
            $request->query('tarikh') ? (string) $request->query('tarikh') : null,
            $request->query('dari') ? (string) $request->query('dari') : null,
            $request->query('hingga') ? (string) $request->query('hingga') : null,
        );
        $number = 'INV-KOM-'.str_replace('_', '-', $period->slug());

        $pdf = Pdf::view('pdf.agent-commission', [
            'rows' => AgentStats::perAgent(Agent::query()->with('user')->get(), $period),
            'period' => $period,
            'number' => $number,
        ])->format('a4');

        $file = 'Invois-Komisen-'.$period->slug().'.pdf';

        return $request->boolean('muat-turun') ? $pdf->download($file) : $pdf->inline($file);
    }
}
