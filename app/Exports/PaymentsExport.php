<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/** Pengesahan Bayaran "Eksport Excel" (exportPayExcel columns & widths). */
class PaymentsExport implements FromCollection, WithColumnWidths, WithHeadings, WithTitle
{
    /** @param  Collection<int, Order>  $orders  with payment + customer */
    public function __construct(private readonly Collection $orders) {}

    /** @return Collection<int, array{string, string, string, string, string, string, string}> */
    public function collection(): Collection
    {
        return $this->orders->map(fn (Order $o) => [
            $o->order_no,
            $o->customer->name,
            $o->package_name,
            rm($o->total_sen),
            $o->payment?->channel ?: $o->payment_method->label(),
            $o->payment?->paid_at ? tarikh($o->payment->paid_at, true) : '-',
            $o->payment?->status->label() ?? '-',
        ]);
    }

    public function title(): string
    {
        return 'Bayaran';
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['No. Tempahan', 'Pelanggan', 'Pakej', 'Jumlah', 'Kaedah', 'Tarikh Bayaran', 'Status'];
    }

    /** @return array<string, int> */
    public function columnWidths(): array
    {
        return ['A' => 22, 'B' => 28, 'C' => 12, 'D' => 12, 'E' => 16, 'F' => 20, 'G' => 18];
    }
}
