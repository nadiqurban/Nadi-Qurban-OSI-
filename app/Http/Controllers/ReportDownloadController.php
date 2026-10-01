<?php

namespace App\Http\Controllers;

use App\Models\ReportExport;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Pusat Laporan download (route group requires reports.view). */
class ReportDownloadController
{
    public function __invoke(ReportExport $report): StreamedResponse
    {
        abort_unless($report->isDone() && $report->path && Storage::disk('local')->exists($report->path), 404);

        return Storage::disk('local')->download($report->path, $report->file_name);
    }
}
