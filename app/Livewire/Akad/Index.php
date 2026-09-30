<?php

namespace App\Livewire\Akad;

use App\Actions\Orders\UpdateOrderDetails;
use App\Actions\Pipeline\RecordAkad;
use App\Enums\AkadMethod;
use App\Enums\Module;
use App\Enums\OrderStage;
use App\Livewire\Concerns\SelectsRows;
use App\Models\AkadRecord;
use App\Models\Order;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Lafaz Akad Wakalah (Lafaz Akad.dc.html): orders with verified payment wait here.
 *
 * @property-read Collection<int, Order> $rows
 * @property-read array{pending: int, done: int} $counts
 */
#[Layout('layouts::app')]
#[Title('Lafaz Akad')]
class Index extends Component
{
    use SelectsRows;

    #[Url(as: 'tab', except: 'menunggu')]
    public string $tab = 'menunggu';

    // Akad modal
    public bool $showAkad = false;

    public ?int $akadOrderId = null;

    public string $method = 'telefon';

    public bool $consent = false;

    // Detail modal
    public bool $showDetail = false;

    public ?int $detailOrderId = null;

    public bool $editing = false;

    /** @var array<string, string> */
    public array $draft = [];

    /** @return Builder<Order> */
    private function pendingQuery(): Builder
    {
        return Order::query()->visibleTo(auth()->user())->where('stage', OrderStage::PaymentVerified);
    }

    /** @return Builder<Order> */
    private function doneQuery(): Builder
    {
        return Order::query()->visibleTo(auth()->user())->whereHas('akad');
    }

    /** @return Collection<int, Order> */
    #[Computed]
    public function rows(): Collection
    {
        return ($this->tab === 'selesai' ? $this->doneQuery() : $this->pendingQuery())
            ->with(['customer', 'country', 'akad'])
            ->latest('updated_at')
            ->limit(300)
            ->get();
    }

    /** @return array{pending: int, done: int} */
    #[Computed]
    public function counts(): array
    {
        return ['pending' => $this->pendingQuery()->count(), 'done' => $this->doneQuery()->count()];
    }

    /** @return list<array{icon: string, tone: string, value: string, label: string}> */
    public function stats(): array
    {
        $season = (int) app(Settings::class)->get('season.year', now()->year);
        $seasonTotal = AkadRecord::query()->whereHas('order', fn ($q) => $q->where('year', $season))->count();
        $remote = AkadRecord::query()->whereIn('method', [AkadMethod::Phone->value, AkadMethod::WhatsApp->value])->count();
        $all = AkadRecord::query()->count();

        return [
            ['icon' => 'hand-heart', 'tone' => 'warning', 'value' => (string) $this->counts['pending'], 'label' => 'Menunggu Akad'],
            ['icon' => 'check-circle', 'tone' => 'success', 'value' => (string) $this->counts['done'], 'label' => 'Akad Selesai'],
            ['icon' => 'scroll', 'tone' => 'primary', 'value' => number_format($seasonTotal), 'label' => 'Jumlah Akad '.$season],
            ['icon' => 'whatsapp-logo', 'tone' => 'info', 'value' => $all > 0 ? round($remote / $all * 100).'%' : '0%', 'label' => 'Melalui Telefon/WA'],
        ];
    }

    /** @return list<int> */
    protected function selectableIds(): array
    {
        return $this->tab === 'menunggu' ? $this->rows->pluck('id')->map(fn ($id) => (int) $id)->values()->all() : [];
    }

    public function canManage(): bool
    {
        return auth()->user()?->can(Module::Akad->managePermission()) ?? false;
    }

    public function updatedTab(): void
    {
        $this->selected = [];
    }

    // ------------------------------------------------------------ akad

    public function openAkad(int $orderId): void
    {
        $this->akadOrderId = $orderId;
        $this->method = AkadMethod::Phone->value;
        $this->consent = false;
        $this->resetValidation();
        $this->showAkad = true;
    }

    public function confirmAkad(RecordAkad $record): void
    {
        $this->authorize(Module::Akad->managePermission());

        $order = Order::query()->findOrFail($this->akadOrderId);

        try {
            $record->handle($order, AkadMethod::from($this->method), $this->consent, $this->actor());
        } catch (ValidationException $e) {
            $this->addError('consent', (string) collect($e->errors())->flatten()->first());

            return;
        }

        $this->showAkad = false;
        $this->refresh();
        $this->dispatch('toast', message: "Akad {$order->order_no} direkodkan — sedia untuk Agihan Negara.");
    }

    public function bulkAkad(RecordAkad $record): void
    {
        $this->authorize(Module::Akad->managePermission());

        $count = 0;

        foreach (Order::query()->whereIn('id', $this->selected)->get() as $order) {
            try {
                $record->handle($order, AkadMethod::Bulk, true, $this->actor());
                $count++;
            } catch (ValidationException) {
                // already done or not ready — skipped
            }
        }

        $this->selected = [];
        $this->tab = 'selesai';
        $this->refresh();
        $this->dispatch('toast', message: "{$count} akad pukal direkodkan.");
    }

    // ------------------------------------------------------------ detail

    public function openDetail(int $orderId): void
    {
        $order = Order::query()->with(['customer', 'country'])->findOrFail($orderId);

        $this->detailOrderId = $order->id;
        $this->editing = false;
        $this->draft = [
            'name' => $order->customer->name,
            'phone' => $order->customer->phone,
            'email' => (string) $order->customer->email,
            'address' => (string) $order->customer->address,
            'postcode' => (string) $order->customer->postcode,
            'city' => (string) $order->customer->city,
        ];
        $this->resetValidation();
        $this->showDetail = true;
    }

    public function startEdit(): void
    {
        $this->authorize(Module::Orders->managePermission());
        $this->editing = true;
    }

    public function saveDetail(UpdateOrderDetails $update): void
    {
        $this->authorize(Module::Orders->managePermission());

        $this->validate([
            'draft.name' => ['required', 'string', 'max:150'],
            'draft.phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{9,20}$/'],
            'draft.email' => ['nullable', 'email', 'max:150'],
            'draft.address' => ['nullable', 'string', 'max:300'],
            'draft.postcode' => ['nullable', 'digits:5'],
            'draft.city' => ['nullable', 'string', 'max:80'],
        ], ['draft.phone.regex' => 'No. telefon tidak sah (cth. 012-3456789).'], [
            'draft.name' => 'nama', 'draft.phone' => 'no. telefon', 'draft.email' => 'emel',
            'draft.address' => 'alamat', 'draft.postcode' => 'poskod', 'draft.city' => 'bandar',
        ]);

        $order = Order::query()->with('customer')->findOrFail($this->detailOrderId);
        $null = fn (string $k) => trim($this->draft[$k] ?? '') ?: null;

        $update->details($order, [
            'name' => trim($this->draft['name']), 'phone' => trim($this->draft['phone']), 'email' => $null('email'),
            'address' => $null('address'), 'postcode' => $null('postcode'), 'city' => $null('city'),
            'state' => $order->customer->state,
        ], [
            'year' => $order->year,
            'implementation_date' => $order->implementation_date?->toDateString(),
            'notes' => $order->notes,
        ], $this->actor());

        $this->editing = false;
        $this->refresh();
        $this->dispatch('toast', message: 'Maklumat pelanggan dikemaskini.');
    }

    private function refresh(): void
    {
        unset($this->rows, $this->counts);
    }

    private function actor(): User
    {
        /** @var User */
        return auth()->user();
    }

    public function render(): mixed
    {
        return view('livewire.akad.index', [
            'canManage' => $this->canManage(),
            'akadOrder' => $this->akadOrderId ? Order::query()->with(['customer', 'akad.witness'])->find($this->akadOrderId) : null,
            'detailOrder' => $this->detailOrderId ? Order::query()->with(['customer', 'country', 'participants', 'payment.order'])->find($this->detailOrderId) : null,
        ]);
    }
}
