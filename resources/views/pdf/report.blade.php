@php
    /** @var array{title: string, subtitle: string, headings: list<string>, rows: list<list<string|int|float>>, summary: array<string, string>} $data */
    $th = 'background:#42481c; color:#fff; font-size:10px; letter-spacing:.4px; text-transform:uppercase; padding:9px 10px; text-align:left;';
    $td = 'padding:8px 10px; font-size:11px; border-bottom:1px solid #E2E8F0;';
@endphp
<x-pdf.layout :title="$data['title']">
    <style>@page { size: A4 landscape; margin: 0; }</style>
    <div style="padding:14mm 14mm 12mm;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #42481c; padding-bottom:14px;">
            @include('pdf.partials.company-header', ['withAddress' => false])
            <div style="text-align:right;">
                <div style="font-size:18px; font-weight:800; color:#1A1D21;">{{ $data['title'] }}</div>
                <div style="font-size:11px; color:#64748B; margin-top:3px;">{{ $data['subtitle'] }}</div>
                <div style="font-size:10.5px; color:#94A3AC; margin-top:2px;">Dijana {{ tarikh(now(), true) }} oleh {{ $report->requester->name ?? 'Sistem' }}</div>
            </div>
        </div>

        @if ($data['summary'])
            <div style="display:flex; gap:10px; margin-top:16px;">
                @foreach ($data['summary'] as $k => $v)
                    <div style="flex:1; background:#F6F8F7; border-radius:8px; padding:10px 12px;">
                        <div style="font-size:9.5px; font-weight:700; letter-spacing:.4px; color:#94A3AC; text-transform:uppercase;">{{ $k }}</div>
                        <div style="font-size:14px; font-weight:800; color:#42481c; margin-top:3px;">{{ $v }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <table style="margin-top:16px;">
            <thead><tr>@foreach ($data['headings'] as $h)<th style="{{ $th }}">{{ $h }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse ($data['rows'] as $row)
                    <tr>@foreach ($row as $cell)<td style="{{ $td }}">{{ $cell }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ count($data['headings']) }}" style="{{ $td }} text-align:center; color:#94A3AC;">Tiada data untuk tempoh ini.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="margin-top:18px; text-align:center; font-size:10px; color:#94A3AC;">Nadi Qurban OSI · Laporan sulit untuk kegunaan dalaman</div>
    </div>
</x-pdf.layout>
