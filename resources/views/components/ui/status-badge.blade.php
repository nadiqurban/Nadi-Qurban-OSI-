@props([
    'status',   // a BackedEnum implementing label() + tone(), or a plain label string
])

@php
    // Fallback map for plain strings = design `st` map in Tempahan & Pelanggan.dc.html.
    $fallback = [
        'Selesai' => 'success', 'Diterima' => 'success', 'Aktif' => 'success', 'Dibayar' => 'success',
        'Dalam Proses' => 'info',
        'Menunggu Bayaran' => 'warning', 'Menunggu' => 'warning', 'Tertunggak' => 'warning', 'Pending' => 'warning',
        'Dibatalkan' => 'danger', 'Batal' => 'danger', 'Lewat' => 'danger', 'Lewat Bayar' => 'danger', 'Digantung' => 'danger',
        'Draf' => 'neutral',
    ];

    if (is_object($status) && method_exists($status, 'label')) {
        $label = $status->label();
        $tone = method_exists($status, 'tone') ? $status->tone() : 'neutral';
    } else {
        $label = (string) $status;
        $tone = $fallback[$label] ?? 'neutral';
    }
@endphp

<x-ui.badge :tone="$tone" {{ $attributes }}>{{ $label }}</x-ui.badge>
