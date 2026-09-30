<?php

namespace App\Livewire\Public;

use App\Actions\Installments\ProcessChipPurchase;
use App\Actions\Installments\StartPortalPayment;
use App\Enums\InstallmentPlanStatus;
use App\Enums\InstallmentStatus;
use App\Enums\PortalPayMethod;
use App\Models\InstallmentPlan;
use App\Models\PaymentGatewayTransaction;
use App\Services\Chip\ChipGateway;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

/**
 * Public /bayar/{token} (Portal Ansuran.dc.html): one permanent link per plan.
 * Pick unpaid months + method → CHIP Collect checkout → back here with ?tx= →
 * the purchase is re-checked with CHIP (idempotent with the webhook).
 */
#[Layout('layouts::portal', ['title' => 'Portal Bayaran Ansuran'])]
#[Title('Portal Bayaran Ansuran')]
class InstallmentPortal extends Component
{
    #[Locked]
    public string $token = '';

    /** @var array<int, bool> seq => selected */
    public array $selected = [];

    public string $method = 'fpx';

    /** null | done | failed */
    public ?string $payState = null;

    public bool $showReceipt = false;

    #[Locked]
    public ?string $txRef = null;

    public function mount(string $token, ChipGateway $chip, ProcessChipPurchase $process): void
    {
        $this->token = $token;
        $plan = $this->plan();

        $ref = request()->query('tx');

        if (! is_string($ref) || $ref === '') {
            return;
        }

        $tx = PaymentGatewayTransaction::query()->where('installment_plan_id', $plan->id)->where('reference', $ref)->first();

        if (! $tx) {
            return;
        }

        if (! $tx->isPaid() && $tx->purchase_id && $chip->isConfigured()) {
            try {
                $tx = $process->handle($chip->getPurchase($tx->purchase_id)) ?? $tx;
            } catch (RuntimeException) {
                // The webhook will reconcile; show the current state.
            }
        }

        $this->txRef = $tx->reference;
        $this->payState = $tx->isPaid() ? 'done' : 'failed';
    }

    private function plan(): InstallmentPlan
    {
        /** @var InstallmentPlan */
        return InstallmentPlan::query()->where('pay_token', $this->token)->with(['customer', 'installments'])->firstOrFail();
    }

    public function toggle(int $seq): void
    {
        $this->selected[$seq] = ! ($this->selected[$seq] ?? false);
    }

    public function pay(StartPortalPayment $start): mixed
    {
        $key = 'portal-pay:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->dispatch('toast', message: 'Terlalu banyak cubaan. Sila cuba semula sebentar lagi.', tone: 'danger');

            return null;
        }

        RateLimiter::hit($key, 60);

        $method = PortalPayMethod::tryFrom($this->method) ?? PortalPayMethod::Fpx;
        $seqs = array_keys(array_filter($this->selected));

        try {
            $tx = $start->handle($this->plan(), array_map('intval', $seqs), $method);
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: (string) collect($e->errors())->flatten()->first(), tone: 'danger');

            return null;
        }

        return $this->redirect((string) $tx->checkout_url);
    }

    public function closePay(): void
    {
        $this->payState = null;
        $this->showReceipt = false;
        $this->selected = [];
    }

    public function render(): mixed
    {
        $plan = $this->plan();
        $unpaid = $plan->installments->where('status', InstallmentStatus::Unpaid);
        $chosen = $unpaid->filter(fn ($i) => $this->selected[$i->seq] ?? false);
        $payAmount = (int) ($chosen->isEmpty() ? ($unpaid->first()->amount_sen ?? 0) : $chosen->sum('amount_sen'));

        return view('livewire.public.installment-portal', [
            'plan' => $plan,
            'payAmount' => $payAmount,
            'open' => ! $plan->sent_at && in_array($plan->status, [InstallmentPlanStatus::Ongoing, InstallmentPlanStatus::Late], true),
            'tx' => $this->txRef ? PaymentGatewayTransaction::query()->where('installment_plan_id', $plan->id)->where('reference', $this->txRef)->first() : null,
        ]);
    }
}
