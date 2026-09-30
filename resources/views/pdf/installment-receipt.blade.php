<x-pdf.layout title="Resit Ansuran" :size="$size">
    @if ($size === 'A5')
        <div style="width:148mm; min-height:210mm; padding:14mm;">
            @include('pdf.partials.installment-receipt-a5', ['plan' => $plan, 'tx' => $tx])
        </div>
    @else
        <div class="page" style="padding:22mm 20mm;">
            @include('pdf.partials.installment-receipt-a4', ['plan' => $plan])
        </div>
    @endif
</x-pdf.layout>
