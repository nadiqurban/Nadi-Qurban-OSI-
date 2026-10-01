<?php

namespace App\Livewire;

use App\Enums\Module;
use App\Exports\TableExport;
use App\Models\Order;
use App\Models\User;
use App\Support\DashboardStats;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Dashboard Operasi: 9 KPIs, Jualan Bulanan + Pencapaian gauge, Agihan Negara
 * map, Ranking Vendor, Prestasi Negara, Aktiviti Terkini, Pakej / Haiwan,
 * Tempahan Terkini. Figures come from DashboardStats (cached 5 minutes).
 */
#[Layout('layouts::app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    /** Cards with a collapse checkbox (design chkJualan … chkTerkini). */
    public const CARDS = ['jualan', 'sasaran', 'negara', 'prestasi', 'pakej', 'haiwan', 'terkini'];

    #[Url(as: 'tarikh', except: '')]
    public string $date = '';

    /** @var list<string> */
    public array $collapsed = [];

    public function mount(): void
    {
        $this->collapsed = array_values(array_intersect($this->user()->dashboard_collapsed ?? [], self::CARDS));
        $this->date = $this->date !== '' ? $this->until()->toDateString() : '';
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }

    private function until(): Carbon
    {
        try {
            return $this->date !== '' ? Carbon::parse($this->date) : today();
        } catch (\Throwable) {
            return today();
        }
    }

    /** Collapse/expand a card; remembered per user. */
    public function toggleCard(string $card): void
    {
        abort_unless(in_array($card, self::CARDS, true), 422);

        $this->collapsed = in_array($card, $this->collapsed, true)
            ? array_values(array_diff($this->collapsed, [$card]))
            : [...$this->collapsed, $card];

        $this->user()->forceFill(['dashboard_collapsed' => $this->collapsed])->save();
    }

    public function refreshData(): void
    {
        DashboardStats::flush();
        $this->dispatch('toast', message: 'Data dashboard dimuat semula.');
    }

    /** @return Collection<int, Order> */
    private function latestOrders(): Collection
    {
        return Order::query()->with(['customer', 'country'])->where('created_at', '<=', $this->until()->copy()->endOfDay())
            ->latest()->latest('id')->limit(8)->get();
    }

    public function export(): BinaryFileResponse
    {
        $this->authorize(Module::Dashboard->viewPermission());
        $data = app(DashboardStats::class)->all($this->until());

        $rows = collect($data['kpis'])->map(fn ($k) => ['KPI', $k['label'], $k['value'], $k['delta']])
            ->concat(collect($data['countries'])->map(fn ($c) => ['Prestasi Negara', $c['name'], $c['orders'].' tempahan', rm_short($c['sales']).' · '.$c['pct'].'%']))
            ->concat(collect($data['packages'])->map(fn ($p) => ['Pakej', $p['name'], (string) $p['count'], '']))
            ->concat(collect($data['animals'])->map(fn ($a) => ['Haiwan', $a['name'], (string) $a['count'], '']))
            ->concat($this->latestOrders()->map(fn (Order $o) => ['Tempahan Terkini', $o->order_no.' — '.$o->customer->name, rm($o->total_sen), $o->status->label()]));

        return Excel::download(new TableExport('Dashboard', ['Bahagian', 'Perkara', 'Nilai', 'Catatan'], $rows, [18, 44, 18, 26]),
            'Dashboard-Nadi-Qurban-'.$this->until()->format('Ymd').'.xlsx');
    }

    public function render(): mixed
    {
        $stats = app(DashboardStats::class);

        return view('livewire.dashboard', [
            'data' => $stats->all($this->until()),
            'season' => $stats->season(),
            'firstName' => explode(' ', trim($this->user()->name))[0],
            'activities' => Activity::query()->with('causer')->latest()->latest('id')->limit(6)->get(),
            'orders' => $this->latestOrders(),
            'untilValue' => $this->until()->toDateString(),
        ]);
    }
}
