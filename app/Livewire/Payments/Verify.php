<?php

namespace App\Livewire\Payments;

use App\Actions\Orders\RejectPayment;
use App\Actions\Orders\VerifyPayment;
use App\Enums\Module;
use App\Enums\OrderStage;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exports\PaymentsExport;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Pengesahan Bayaran: orders marked "Diterima" wait here. Sahkan → stage
 * payment_verified (on to Lafaz Akad); Batal → back to Menunggu Bayaran with a reason.
 *
 * @property-read Collection<int, Order> $rows
 * @property-read array{pending: int, done: int} $counts
 */
#[Layout('layouts::app')]
#[Title('Pengesahan Bayaran')]
class Verify extends Component
{
    #[Url(as: 'tab', except: 'belum')]
    public string $tab = 'belum';

    #[Url(as: 'tarikh', except: '')]
    public string $date = '';

    /** @var list<int> */
    public array $checked = [];

    public bool $showProof = false;

    public ?int $proofOrderId = null;

    public bool $showReject = false;

    public ?int $rejectOrderId = null;

    public string $reason = '';

    /** @return Builder<Order> */
    private function pendingQuery(): Builder
    {
        return Order::query()
            ->where('status', OrderStatus::Accepted)
            ->whereHas('payment', fn ($q) => $q->where('status', PaymentStatus::Pending));
    }

    /** @return Builder<Order> */
    private function doneQuery(): Builder
    {
        return Order::query()
            ->whereHas('payment', fn ($q) => $q->where('status', PaymentStatus::Verified))
            ->where('stage', '!=', OrderStage::Received);
    }

    /** @return Builder<Order> */
    private function currentQuery(): Builder
    {
        return ($this->tab === 'telah' ? $this->doneQuery() : $this->pendingQuery())
            ->when($this->date !== '', fn ($q) => $q->whereHas('payment', fn ($p) => $p->whereDate('paid_at', $this->date)))
            ->with(['customer', 'payment.media', 'payment.order'])
            ->latest('accepted_at')
            ->latest('id');
    }

    /** @return Collection<int, Order> */
    #[Computed]
    public function rows(): Collection
    {
        return $this->currentQuery()->limit(200)->get();
    }

    /** @return array{pending: int, done: int} */
    #[Computed]
    public function counts(): array
    {
        return ['pending' => $this->pendingQuery()->count(), 'done' => $this->doneQuery()->count()];
    }

    public function canManage(): bool
    {
        return auth()->user()?->can(Module::Payments->managePermission()) ?? false;
    }

    public function updatedTab(): void
    {
        $this->checked = [];
    }

    public function viewProof(int $orderId): void
    {
        $this->proofOrderId = $orderId;
        $this->showProof = true;
    }

    public function confirm(int $orderId, VerifyPayment $verify): void
    {
        $this->authorize(Module::Payments->managePermission());

        $order = Order::query()->findOrFail($orderId);

        try {
            $verify->handle($order, $this->actor());
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: collect($e->errors())->flatten()->first(), tone: 'danger');

            return;
        }

        $this->refreshLists();
        $this->dispatch('toast', message: "Bayaran {$order->order_no} disahkan — tempahan dihantar ke Lafaz Akad.");
    }

    /** "Sahkan" for every ticked row. */
    public function confirmChecked(VerifyPayment $verify): void
    {
        $this->authorize(Module::Payments->managePermission());

        $ok = 0;
        $failed = [];

        foreach (Order::query()->whereIn('id', $this->checked)->get() as $order) {
            try {
                $verify->handle($order, $this->actor());
                $ok++;
            } catch (ValidationException) {
                $failed[] = $order->order_no;
            }
        }

        $this->checked = [];
        $this->refreshLists();
        $this->dispatch('toast', message: "{$ok} bayaran disahkan.".($failed ? ' Gagal: '.implode(', ', $failed) : ''), tone: $failed ? 'info' : 'success');
    }

    public function openReject(int $orderId): void
    {
        $this->authorize(Module::Payments->managePermission());

        $this->rejectOrderId = $orderId;
        $this->reason = '';
        $this->resetValidation();
        $this->showReject = true;
    }

    public function reject(RejectPayment $reject): void
    {
        $this->authorize(Module::Payments->managePermission());

        $this->validate(['reason' => ['required', 'string', 'min:5', 'max:300']], attributes: ['reason' => 'sebab pembatalan']);

        $order = Order::query()->findOrFail($this->rejectOrderId);

        try {
            $reject->handle($order, trim($this->reason), $this->actor());
        } catch (ValidationException $e) {
            $this->addError('reason', (string) collect($e->errors())->flatten()->first());

            return;
        }

        $this->showReject = false;
        $this->refreshLists();
        $this->dispatch('toast', message: "Bayaran {$order->order_no} dibatalkan dan dikembalikan ke Menunggu Bayaran.");
    }

    public function export(): BinaryFileResponse
    {
        $this->authorize(Module::Payments->viewPermission());

        return Excel::download(new PaymentsExport($this->currentQuery()->get()), 'Pengesahan-Bayaran-Nadi-Qurban.xlsx');
    }

    private function refreshLists(): void
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
        return view('livewire.payments.verify', [
            'canManage' => $this->canManage(),
            'proofOrder' => $this->proofOrderId ? Order::query()->with('payment.media')->find($this->proofOrderId) : null,
            'rejectOrder' => $this->rejectOrderId ? Order::query()->with('customer')->find($this->rejectOrderId) : null,
        ]);
    }
}
