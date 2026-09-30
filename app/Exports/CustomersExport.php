<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * "Eksport Excel" in the waybill panel — the 5 columns and widths from
 * exportCustomersExcel() in Tempahan & Pelanggan.dc.html.
 */
class CustomersExport implements FromCollection, WithColumnWidths, WithHeadings, WithTitle
{
    /** @param  Collection<int, Order>  $orders */
    public function __construct(private readonly Collection $orders) {}

    /** @return Collection<int, array{string, string, string, string, string}> */
    public function collection(): Collection
    {
        return $this->orders->map(function (Order $o) {
            $c = $o->customer;

            return [
                $c->name,
                $c->phone,
                (string) $c->postcode,
                collect([$c->address, trim($c->postcode.' '.$c->city), $c->state])->filter()->implode(', '),
                $o->service->label().' — '.$o->animal->label().' ('.$o->quantity.')',
            ];
        });
    }

    public function title(): string
    {
        return 'Pelanggan';
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['Nama Penuh Pelanggan', 'No Telefon', 'Poskod Pelanggan', 'Alamat Penuh Pelanggan', 'Pakej Pelanggan'];
    }

    /** @return array<string, int> */
    public function columnWidths(): array
    {
        return ['A' => 28, 'B' => 16, 'C' => 14, 'D' => 44, 'E' => 26];
    }
}
