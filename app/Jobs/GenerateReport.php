<?php

namespace App\Jobs;

use App\Enums\NotificationType;
use App\Enums\ReportType;
use App\Exports\TableExport;
use App\Models\ReportExport;
use App\Notifications\AppNotification;
use App\Reports\ReportBuilder;
use App\Support\DocumentRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\LaravelPdf\Facades\Pdf;
use Throwable;

/** Pusat Laporan: build the data, write PDF / XLSX / CSV to the private disk, mark Siap. */
class GenerateReport implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(public ReportExport $report) {}

    public function handle(ReportBuilder $builder, DocumentRegistry $documents): void
    {
        $data = $builder->build($this->report);
        $path = 'reports/'.$this->report->id.'-'.$this->report->file_name;

        match ($this->report->format) {
            'pdf' => $this->pdf($data, $path),
            'csv' => Excel::store(new TableExport($data['title'], $data['headings'], $data['rows'], $data['widths']), $path, 'local', ExcelFormat::CSV),
            default => Excel::store(new TableExport($data['title'], $data['headings'], $data['rows'], $data['widths']), $path, 'local', ExcelFormat::XLSX),
        };

        $this->report->forceFill([
            'status' => ReportExport::DONE,
            'path' => $path,
            'size' => Storage::disk('local')->exists($path) ? Storage::disk('local')->size($path) : null,
            'completed_at' => now(),
            'error' => null,
        ])->save();

        $documents->report($this->report);
        $this->report->requester?->notify(new AppNotification(
            NotificationType::SystemAlert,
            'Laporan dijana',
            "{$this->report->name} telah siap dimuat turun.",
            route('reports.index'),
            $this->report->type === ReportType::Finance ? 'Kewangan' : 'Sistem',
        ));
    }

    /** @param array<string, mixed> $data */
    private function pdf(array $data, string $path): void
    {
        Storage::disk('local')->makeDirectory(dirname($path));
        Pdf::view('pdf.report', ['data' => $data, 'report' => $this->report])->format('a4')->landscape()
            ->save(Storage::disk('local')->path($path));
    }

    public function failed(Throwable $e): void
    {
        $this->report->forceFill(['status' => ReportExport::FAILED, 'error' => mb_substr($e->getMessage(), 0, 500)])->save();
    }
}
