<x-pdf.layout :title="$doc->number">
    <div class="page" style="padding:22mm 20mm;">
        @include('pdf.partials.finance-doc-a4', ['doc' => $doc])
    </div>
</x-pdf.layout>
