<?php

namespace App\Support;

use App\Enums\OrderStage;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStageHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Display rows shared by the order detail page and the printed receipt
 * (custInfo / orderInfo in Tempahan & Pelanggan.dc.html).
 */
final class OrderPresenter
{
    /** @return list<array{k: string, v: string}> */
    public static function customerInfo(Order $order): array
    {
        $c = $order->customer;

        return [
            ['k' => 'Customer ID', 'v' => $c->code],
            ['k' => 'Nama', 'v' => $c->name],
            ['k' => 'Telefon', 'v' => $c->phone],
            ['k' => 'Emel', 'v' => $c->email ?: '-'],
            ['k' => 'Alamat', 'v' => $c->address ?: '-'],
            ['k' => 'Poskod / Negeri', 'v' => collect([$c->postcode, $c->city, $c->state])->filter()->implode(' · ') ?: '-'],
        ];
    }

    /** @return list<array{k: string, v: string}> */
    public static function orderInfo(Order $order): array
    {
        $rows = [
            ['k' => 'Servis', 'v' => $order->service->label()],
            ['k' => 'Haiwan', 'v' => $order->animal->label()],
            ['k' => 'Negara Pelaksanaan', 'v' => $order->country->name],
            ['k' => 'Kuantiti', 'v' => (string) $order->quantity],
            ['k' => 'Pakej', 'v' => $order->package_name],
            ['k' => 'Tahun Pelaksanaan', 'v' => (string) $order->year],
        ];

        if ($order->implementation_date) {
            $rows[] = ['k' => 'Tarikh Pelaksanaan', 'v' => tarikh($order->implementation_date)];
        }

        if ($order->promo_code) {
            $rows[] = ['k' => 'Kod Promosi', 'v' => $order->promo_code.' (- '.rm($order->discount_sen).')'];
        }

        $rows[] = ['k' => 'Harga', 'v' => rm($order->total_sen)];

        if ($order->source === 'public') {
            $rows[] = ['k' => 'Sumber', 'v' => 'Tempahan Awam (dalam talian)'];
        }

        if ($order->agent) {
            $rows[] = ['k' => 'Ejen', 'v' => $order->agent->user->name.' ('.$order->agent->code.') · komisen '.rm($order->commission_sen)];
        }

        return $rows;
    }

    /**
     * 12-step workflow for the stepper with timestamps from order_stage_histories.
     *
     * @return list<array{label: string, state: string, time: ?string}>
     */
    public static function workflow(Order $order): array
    {
        /** @var Collection<string, OrderStageHistory> $history */
        $history = $order->stageHistories->keyBy(fn ($h) => $h->stage->value);
        $current = $order->stage;
        $completed = $current === OrderStage::Completed;
        $cancelled = $order->status === OrderStatus::Cancelled;

        return collect(OrderStage::cases())->map(function (OrderStage $stage) use ($history, $current, $completed, $cancelled) {
            $done = $completed || $stage->position() <= $current->position();
            $isNext = ! $completed && ! $cancelled && $stage->position() === $current->position() + 1;
            $entry = $history->get($stage->value);

            return [
                'label' => $stage->label(),
                'state' => $done ? 'done' : ($isNext ? 'current' : 'pending'),
                'time' => $done && $entry ? self::shortStamp($entry->created_at) : null,
            ];
        })->values()->all();
    }

    /** "12 Jun, 09:14" */
    public static function shortStamp(\DateTimeInterface $at): string
    {
        $months = ['Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ogos', 'Sep', 'Okt', 'Nov', 'Dis'];
        $c = Carbon::instance($at);

        return $c->day.' '.$months[$c->month - 1].', '.$c->format('H:i');
    }
}
