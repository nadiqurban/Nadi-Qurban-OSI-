<x-pdf.layout :title="$po->po_no">
    <div class="page" style="padding:36px 40px;">
        @include('pdf.partials.purchase-order-body', ['po' => $po])
    </div>
</x-pdf.layout>
