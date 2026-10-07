<?php

namespace App\Livewire\Public;

use App\Enums\OrderStage;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\Settings;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Public "Jejak Status Tempahan" (Tracking Pelanggan.dc.html). Search by tracking
 * no. or order no.; the main participant is shown in full, other names are masked unless the link carries the
 * order's secret token (?t=…). Rate-limited per IP.
 */
#[Layout('layouts::public', ['title' => 'Jejak Status'])]
#[Title('Jejak Status')]
class Tracking extends Component
{
    /** Customer-facing steps: [title, description]. */
    public const STEPS = [
        ['Tempahan Diterima', 'Tempahan anda telah direkodkan.'],
        ['Bayaran Disahkan', 'Pembayaran anda telah disahkan.'],
        ['Lafaz Akad', 'Akad telah dilaksanakan.'],
        ['Agihan Negara', 'Diamanahkan kepada vendor pelaksana.'],
        ['Ibadah Dilaksanakan', 'Ibadah selesai, laporan disahkan.'],
        ['Sijil Dihantar', 'Sijil & bukti dalam perjalanan.'],
        ['Selesai', 'Amanah anda telah ditunaikan.'],
    ];

    /** Pipeline stage that completes each customer step (index = step). */
    private const STEP_STAGE = [
        OrderStage::Received, OrderStage::PaymentVerified, OrderStage::AkadDone, OrderStage::VendorAssigned,
        OrderStage::ReportVerified, OrderStage::AwbGenerated, OrderStage::Completed,
    ];

    #[Url(as: 'track', except: '')]
    public string $query = '';

    #[Url(as: 't', except: '')]
    public string $token = '';

    public string $input = '';

    public bool $throttled = false;

    public function mount(): void
    {
        $this->input = $this->query;
    }

    public function search(): void
    {
        $this->query = Str::upper(trim($this->input));
        $this->token = '';
    }

    public static function mask(string $name): string
    {
        $words = preg_split('/\s+/', trim($name)) ?: [];
        $count = count($words);

        return collect($words)->map(function (string $word, int $i) use ($count) {
            if ($i === 0 || ($i === $count - 1 && $count >= 3)) {
                return $word;
            }

            return mb_substr($word, 0, 1).'***';
        })->implode(' ');
    }

    private function find(): ?Order
    {
        if ($this->query === '') {
            return null;
        }

        $key = 'jejak:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 20)) {
            $this->throttled = true;

            return null;
        }

        RateLimiter::hit($key, 60);
        $this->throttled = false;

        return Order::query()
            ->where(fn ($q) => $q->where('tracking_no', $this->query)->orWhere('order_no', $this->query))
            ->whereNotIn('status', [OrderStatus::Draft, OrderStatus::Cancelled])
            ->with(['customer', 'country', 'participants', 'stageHistories'])
            ->first();
    }

    public function render(): mixed
    {
        $order = $this->find();
        $data = ['order' => $order, 'settings' => app(Settings::class)];

        if ($order) {
            $unmasked = $this->token !== '' && hash_equals($order->tracking_token, $this->token);
            $name = fn (string $n) => $unmasked ? $n : self::mask($n);
            $done = collect(self::STEP_STAGE)->filter(fn (OrderStage $s) => $order->stage->position() >= $s->position())->count();

            $data += [
                // Participant 1 is the main participant: shown in full like "Peserta Utama".
                'names' => $order->participantNames()->map(fn (string $n, int $i) => $i === 0 && $n !== '' ? $n : $name($n ?: 'Peserta '.($i + 1)))->all(),
                'mainName' => $order->customer->name,   // shown in full (business request); other names stay masked
                'done' => $done,
                'pct' => (int) round($done / count(self::STEPS) * 100),
                'times' => collect(self::STEP_STAGE)->map(fn (OrderStage $s) => $order->stageHistories->firstWhere('stage', $s)?->created_at)->all(),
            ];
        }

        return view('livewire.public.tracking', $data);
    }
}
