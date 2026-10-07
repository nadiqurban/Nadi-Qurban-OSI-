<?php

namespace App\Livewire\Agent;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Agent;
use App\Models\Order;
use App\Support\AgentStats;
use App\Support\Period;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Portal Ejen (Portal Ejen.dc.html): the agent's link, stats for a period and the
 * orders that came through the link. Read-only: orders go straight to HQ, so the
 * prototype's "Hantar / Batal / Sahkan bukti" actions are not offered.
 *
 * @property-read Agent $agent
 * @property-read Collection<int, Order> $orders
 */
#[Layout('layouts::agent')]
#[Title('Portal Ejen')]
class Portal extends Component
{
    public const TABS = ['pending' => 'Belum Disahkan', 'confirmed' => 'Disahkan', 'archive' => 'Arkib Tempahan'];

    #[Url(as: 'tempoh', except: 'all')]
    public string $period = 'all';

    public string $refDate = '';

    #[Url(as: 'tab', except: 'pending')]
    public string $tab = 'pending';

    public ?int $proofOrderId = null;

    public bool $showProof = false;

    public function mount(): void
    {
        $this->refDate = $this->refDate ?: today()->toDateString();
    }

    #[Computed]
    public function agent(): Agent
    {
        return Agent::query()->with('user')->where('user_id', auth()->id())->firstOrFail();
    }

    public function periodFilter(): Period
    {
        return new Period($this->period, $this->refDate ?: null);
    }

    /** @return Collection<int, Order> all of the agent's orders in the period */
    #[Computed]
    public function orders(): Collection
    {
        return $this->periodFilter()
            ->apply(Order::query()->where('agent_id', $this->agent->id))
            ->with(['customer', 'payment'])
            ->latest()
            ->latest('id')
            ->get();
    }

    public static function group(Order $order): string
    {
        return match (true) {
            in_array($order->status, [OrderStatus::Completed, OrderStatus::Cancelled], true) => 'archive',
            $order->payment?->status === PaymentStatus::Verified => 'confirmed',
            default => 'pending',
        };
    }

    /** @return array{0: string, 1: string} label + tone for the customer's payment state */
    public static function statusOf(Order $order): array
    {
        return match (true) {
            $order->status === OrderStatus::Cancelled => ['Dibatalkan', 'danger'],
            $order->status === OrderStatus::Completed => ['Selesai', 'success'],
            $order->payment?->status === PaymentStatus::Verified => ['Dibayar', 'success'],
            $order->payment?->status === PaymentStatus::Rejected => ['Ditolak', 'danger'],
            default => ['Menunggu Pengesahan', 'warning'],
        };
    }

    public function setPeriod(string $key): void
    {
        $this->period = in_array($key, ['all', 'day', 'month', 'year'], true) ? $key : 'all';
        unset($this->orders);
    }

    public function updatedRefDate(): void
    {
        if ($this->period === 'all') {
            $this->period = 'day';
        }
        unset($this->orders);
    }

    public function openProof(int $orderId): void
    {
        $this->proofOrderId = $this->orders->firstWhere('id', $orderId)?->id;
        $this->showProof = $this->proofOrderId !== null;
    }

    public function render(): mixed
    {
        $period = $this->periodFilter();
        $orders = $this->orders;
        $live = $orders->reject(fn (Order $o) => $o->status === OrderStatus::Cancelled);
        $counted = $live->filter(fn (Order $o) => $o->payment?->status === PaymentStatus::Verified);
        $grouped = $orders->groupBy(fn (Order $o) => self::group($o));
        $tab = array_key_exists($this->tab, self::TABS) ? $this->tab : 'pending';

        return view('livewire.agent.portal', [
            'p' => $period,
            'tab' => $tab,
            'list' => $grouped->get($tab, new Collection),
            'counts' => collect(self::TABS)->map(fn ($label, $key) => $grouped->get($key, new Collection)->count())->all(),
            'proofOrder' => $this->proofOrderId ? $orders->firstWhere('id', $this->proofOrderId) : null,
            'stats' => [
                ['icon' => 'cursor-click', 'tone' => 'info', 'value' => number_format(AgentStats::clicks($this->agent, $period)), 'label' => 'Klik Link'],
                ['icon' => 'shopping-cart-simple', 'tone' => 'primary', 'value' => (string) $orders->count(), 'label' => 'Tempahan Terhasil'],
                ['icon' => 'chart-line-up', 'tone' => 'warning', 'value' => rm((int) $live->sum('total_sen'), true), 'label' => 'Jumlah Jualan'],
                ['icon' => 'hand-coins', 'tone' => 'warning', 'value' => rm((int) $live->sum('commission_sen'), true), 'label' => 'Komisen Ejen'],
                ['icon' => 'seal-check', 'tone' => 'success', 'value' => rm((int) $counted->sum('commission_sen'), true), 'label' => 'Komisen Disahkan ('.$counted->count().' tempahan)'],
            ],
        ]);
    }
}
