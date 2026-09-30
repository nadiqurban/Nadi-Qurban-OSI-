<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Simple sheet for the list screens' "Eksport Excel" buttons (Agihan, Pelaksanaan, AWB,
 * Tempahan Selesai): headings + rows + column widths (wch) from the design's exporter.
 */
class TableExport implements FromCollection, WithColumnWidths, WithHeadings, WithTitle
{
    /**
     * @param  list<string>  $headings
     * @param  iterable<int, array<int, mixed>>  $rows
     * @param  list<int>  $widths
     */
    public function __construct(
        private readonly string $title,
        private readonly array $headings,
        private readonly iterable $rows,
        private readonly array $widths = [],
    ) {}

    /** @return Collection<int, array<int, mixed>> */
    public function collection(): Collection
    {
        return collect($this->rows)->values();
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }

    /** @return list<string> */
    public function headings(): array
    {
        return $this->headings;
    }

    /** @return array<string, int> */
    public function columnWidths(): array
    {
        $widths = [];

        foreach ($this->widths as $i => $w) {
            $widths[$this->column($i)] = $w;
        }

        return $widths;
    }

    private function column(int $i): string
    {
        $name = '';

        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $name = chr(65 + ($i - 1) % 26).$name;
        }

        return $name;
    }
}
