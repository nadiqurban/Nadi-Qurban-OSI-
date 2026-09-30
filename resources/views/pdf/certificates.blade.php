@php
    /** @var list<array<string, string|null>> $certificates  CertificateTemplate::render() per page */
@endphp
<x-pdf.layout title="Sijil" size="A5">
    @foreach ($certificates as $c)
        {{-- 472px design width → 148mm (A5) --}}
        <div style="width:148mm; height:210mm; overflow:hidden; {{ $loop->last ? '' : 'page-break-after:always;' }}">
            <div style="zoom:1.1851;">
                @include('pdf.partials.certificate', ['c' => $c])
            </div>
        </div>
    @endforeach
</x-pdf.layout>
