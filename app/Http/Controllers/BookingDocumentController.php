<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

/** Tempahan Awam: "Muat Turun PDF" of the Resit Bayaran (key = the order's secret tracking token). */
class BookingDocumentController
{
    public function receipt(string $token): PdfBuilder
    {
        $order = Order::query()->with(['customer', 'country', 'participants', 'payment'])
            ->where('source', 'public')->where('tracking_token', $token)->firstOrFail();

        return Pdf::view('pdf.booking-receipt', ['order' => $order])
            ->format('a4')
            ->download('Resit-'.$order->order_no.'.pdf');
    }
}
