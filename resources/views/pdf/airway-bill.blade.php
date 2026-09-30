@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Shipment> $shipments */
    $company = app(\App\Support\Settings::class)->group('company');
@endphp
<x-pdf.layout title="Airway Bill">
    @foreach ($shipments as $shipment)
        <div class="page" style="padding:44px 52px;">
            @include('pdf.partials.airway-bill-body', ['shipment' => $shipment, 'company' => $company])
        </div>
    @endforeach
</x-pdf.layout>
