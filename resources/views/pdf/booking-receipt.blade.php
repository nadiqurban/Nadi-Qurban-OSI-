<x-pdf.layout :title="'Resit '.$order->order_no">
    <div class="page" style="padding:20mm 18mm;">
        @include('pdf.partials.booking-receipt-a4', ['order' => $order])
    </div>
</x-pdf.layout>
