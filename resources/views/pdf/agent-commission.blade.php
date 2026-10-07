<x-pdf.layout :title="$number">
    <div class="page" style="padding:16mm 15mm;">
        @include('pdf.partials.agent-commission-a4', ['rows' => $rows, 'period' => $period, 'number' => $number])
    </div>
</x-pdf.layout>
