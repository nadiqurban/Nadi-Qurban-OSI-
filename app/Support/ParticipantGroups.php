<?php

namespace App\Support;

use App\Enums\Animal;
use App\Models\Order;
use Illuminate\Support\Collection;

/**
 * "Senarai Peserta Mengikut Kumpulan": participant names chunked by animal capacity
 * (Lembu & Unta 7 names per animal, Kambing 1), per order.
 */
final class ParticipantGroups
{
    /**
     * @param  Collection<int, Order>  $orders  with customer, country, participants loaded
     * @return list<array{title: string, order_no: string, country: string, animal: string, capacity: int, count: int, full: bool, meta: string, names: list<string>}>
     */
    public static function for(Collection $orders): array
    {
        $groups = [];

        foreach ($orders as $order) {
            $capacity = $order->animal->capacity();
            $names = $order->participantNames()->map(fn (string $n) => mb_strtoupper($n ?: '—'))->values()->all();

            foreach (array_chunk($names, $capacity) as $chunk) {
                $full = count($chunk) === $capacity;

                $groups[] = [
                    'title' => $order->animal->label().' — '.$order->order_no,
                    'order_no' => $order->order_no,
                    'country' => $order->country->name,
                    'animal' => $order->animal->label(),
                    'capacity' => $capacity,
                    'count' => count($chunk),
                    'full' => $full,
                    'meta' => count($chunk).' / '.$capacity.' nama'.($capacity === 7 ? ' (1 ekor = 7 bahagian)' : ''),
                    'names' => $chunk,
                ];
            }
        }

        return $groups;
    }

    /**
     * "#KORBANLEMBU #001243" / "#KORBANLEMBU #001241-001248" (reference PDF header tag).
     *
     * @param  Collection<int, Order>  $orders
     */
    public static function tag(Collection $orders): string
    {
        $first = $orders->first();

        if (! $first instanceof Order) {
            return '#SENARAIPESERTA';
        }

        $animal = match ($first->animal) {
            Animal::Cow => 'LEMBU',
            Animal::Camel => 'UNTA',
            Animal::Goat => 'KAMBING',
        };

        $seqs = $orders->map(fn (Order $o) => substr($o->order_no, -6))->sort()->values();

        return '#KORBAN'.$animal.' #'.$seqs->first().($seqs->count() > 1 ? '-'.$seqs->last() : '');
    }
}
