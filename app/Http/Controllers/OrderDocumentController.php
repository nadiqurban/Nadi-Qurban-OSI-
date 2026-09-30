<?php

namespace App\Http\Controllers;

use App\Enums\Courier;
use App\Enums\Module;
use App\Enums\Severity;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Audit;
use App\Support\ParticipantGroups;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Server-side PDFs (receipt, waybill, participant groups) and the private
 * payment-proof stream. PDFs open inline in a new tab; ?muat-turun=1 downloads.
 */
class OrderDocumentController extends Controller
{
    public function receipt(Request $request, Order $order): PdfBuilder
    {
        Gate::authorize(Module::Orders->viewPermission());

        $order->load(['customer', 'country', 'participants']);

        return $this->respond($request, Pdf::view('pdf.order-receipt', ['order' => $order]), 'Resit-'.$order->order_no.'.pdf');
    }

    public function waybill(Request $request): PdfBuilder
    {
        Gate::authorize(Module::Orders->viewPermission());

        $courier = Courier::tryFrom((string) $request->query('kurier')) ?? Courier::PosLaju;
        $orders = $this->selected($request);

        Audit::log('order.waybill', 'Waybill dijana ('.$orders->count().' tempahan, '.$courier->label().')', severity: Severity::Info,
            properties: ['orders' => $orders->pluck('order_no')->all()], causer: $request->user(), logName: 'orders');

        return $this->respond($request, Pdf::view('pdf.waybill', ['orders' => $orders, 'courier' => $courier]), 'Waybill-Nadi-Qurban.pdf');
    }

    public function participants(Request $request): PdfBuilder
    {
        Gate::authorize(Module::Orders->viewPermission());

        $orders = $this->selected($request);
        $date = $request->filled('tarikh') ? Carbon::parse((string) $request->query('tarikh')) : ($orders->first()->implementation_date ?? now());

        return $this->respond($request, Pdf::view('pdf.participant-groups', [
            'groups' => ParticipantGroups::for($orders),
            'date' => $date->format('d-m-Y'),
            'tag' => ParticipantGroups::tag($orders),
        ]), 'Senarai-Peserta-Nadi-Qurban.pdf');
    }

    /** Streams a private payment proof. Route is signed + authenticated + orders.view. */
    public function proof(Payment $payment): StreamedResponse
    {
        Gate::authorize(Module::Orders->viewPermission());

        $media = $payment->proof() ?? abort(404);

        return response()->stream(function () use ($media) {
            $stream = $media->stream();
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="'.$media->file_name.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=600',
        ]);
    }

    /** @return Collection<int, Order> */
    private function selected(Request $request): Collection
    {
        $ids = array_filter(array_map('intval', (array) $request->query('ids', [])));

        abort_if($ids === [] || count($ids) > 500, 422, 'Sila pilih sekurang-kurangnya satu tempahan.');

        return Order::query()->with(['customer', 'country', 'participants'])->whereIn('id', $ids)->orderBy('order_no')->get();
    }

    private function respond(Request $request, PdfBuilder $pdf, string $name): PdfBuilder
    {
        $pdf->format('a4');

        return $request->boolean('muat-turun') ? $pdf->download($name) : $pdf->inline($name);
    }
}
