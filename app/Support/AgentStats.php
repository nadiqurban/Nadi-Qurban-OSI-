<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Agent;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Agent sales & commission. An order counts once its payment is verified (CHIP paid
 * or HQ verified) and it is not cancelled; commission = snapshot on the order.
 */
final class AgentStats
{
    /**
     * Orders that count towards sales/commission.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public static function counted(Builder $query): Builder
    {
        return $query->whereNotNull('agent_id')
            ->where('status', '!=', OrderStatus::Cancelled)
            ->whereHas('payment', fn (Builder $p) => $p->where('status', PaymentStatus::Verified));
    }

    /**
     * Per-agent totals for the period, highest sales first (agents with no sales included).
     *
     * @param  Collection<int, Agent>  $agents
     * @return Collection<int, array{agent: Agent, count: int, sales_sen: int, commission_sen: int}>
     */
    public static function perAgent(Collection $agents, Period $period): Collection
    {
        $totals = $period->apply(self::counted(Order::query()))
            ->selectRaw('agent_id, COUNT(*) as n, COALESCE(SUM(total_sen), 0) as sales, COALESCE(SUM(commission_sen), 0) as comm')
            ->groupBy('agent_id')
            ->toBase()
            ->get()
            ->keyBy('agent_id');

        return $agents
            ->map(function (Agent $agent) use ($totals) {
                $row = $totals->get($agent->id);

                return [
                    'agent' => $agent,
                    'count' => (int) ($row->n ?? 0),
                    'sales_sen' => (int) ($row->sales ?? 0),
                    'commission_sen' => (int) ($row->comm ?? 0),
                ];
            })
            ->sortByDesc('sales_sen')
            ->values();
    }

    /** Link clicks for one agent within the period. */
    public static function clicks(Agent $agent, Period $period): int
    {
        return (int) $period->apply($agent->clicks()->getQuery(), 'date', dateOnly: true)->sum('clicks');
    }
}
