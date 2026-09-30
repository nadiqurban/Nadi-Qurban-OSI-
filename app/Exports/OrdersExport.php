<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/** "Eksport" in Senarai Tempahan — the list columns. @implements WithMapping<Order> */
class OrdersExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    /** @param  Builder<Order>  $query */
    public function __construct(private readonly Builder $query) {}

    /** @return Builder<Order> */
    public function query(): Builder
    {
        return $this->query->with(['customer', 'country', 'payment.order']);
    }

    public function title(): string
    {
        return 'Tempahan';
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['No. Tempahan', 'No. Tracking', 'Pelanggan', 'Telefon', 'Servis', 'Haiwan', 'Pakej', 'Negara', 'Kuantiti', 'Harga (RM)', 'Bayaran', 'Tahun', 'Status', 'Tarikh Tempahan'];
    }

    /** @return list<string|int|float> */
    public function map($order): array
    {
        return [
            $order->order_no,
            $order->tracking_no,
            $order->customer->name,
            $order->customer->phone,
            $order->service->label(),
            $order->animal->label(),
            $order->package_name,
            $order->country->name,
            $order->quantity,
            $order->total_sen / 100,
            $order->payment ? $order->payment->pill()['label'] : $order->payment_method->label(),
            $order->year,
            $order->status->label(),
            $order->created_at->format('d/m/Y H:i'),
        ];
    }
}
