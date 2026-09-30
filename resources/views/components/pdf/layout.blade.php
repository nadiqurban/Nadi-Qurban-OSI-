@props(['title' => 'Dokumen', 'size' => 'A4'])
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        {!! \App\Support\PdfAssets::fontFaces() !!}
        @page { size: {{ $size }}; margin: 0; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { font-family: 'Inter', Arial, sans-serif; color: #1A1D21; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .page { width: 210mm; min-height: 297mm; padding: 48px 56px; display: flex; flex-direction: column; page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .muted { color: #64748B; }
        .faint { color: #94A3AC; }
        .olive { color: #42481c; }
        .label { font-size: 11px; font-weight: 700; letter-spacing: .5px; color: #94A3AC; text-transform: uppercase; }
        table { border-collapse: collapse; width: 100%; }
    </style>
</head>
<body>
    {{ $slot }}
</body>
</html>
