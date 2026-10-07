<?php

namespace App\Livewire\Public;

use App\Actions\Booking\CreatePublicBooking;
use App\Actions\Booking\StartBookingPayment;
use App\Actions\Pricing\CalculatePrice;
use App\Actions\Pricing\PriceBreakdown;
use App\Enums\PaymentMethod;
use App\Enums\Service;
use App\Enums\UserStatus;
use App\Models\Agent;
use App\Models\Product;
use App\Models\PromoCode;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Tempahan Awam (Tempahan Awam.dc.html): welcome → 1 Pilih Pakej → 2 Maklumat →
 * 3 Bayar. /tempah or an agent link /e/{slug} (also /tempah?ref=CODE).
 *
 * @property-read Agent|null $agent
 * @property-read Collection<int, Product> $products
 * @property-read Product|null $product
 */
#[Layout('layouts::booking')]
#[Title('Tempahan Ibadah Dalam Talian')]
class Booking extends Component
{
    use WithFileUploads;

    public const STATES = [
        'Selangor', 'Kuala Lumpur', 'Putrajaya', 'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan',
        'Pahang', 'Perak', 'Perlis', 'Pulau Pinang', 'Sabah', 'Sarawak', 'Terengganu', 'Labuan',
    ];

    public const MAX_PARTICIPANTS = 7;

    #[Locked]
    public ?int $agentId = null;

    public bool $started = false;

    public int $step = 1;

    public string $service = 'qurban';

    public ?int $productId = null;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $address = '';

    public string $postcode = '';

    public string $city = '';

    public string $state = 'Selangor';

    /** @var list<string> */
    public array $participants = [''];

    public string $promo = '';

    public string $promoApplied = '';

    public string $payType = 'fpx';

    /** @var UploadedFile|null */
    public $proof = null;

    public bool $akad = false;

    public bool $firstParticipantEdited = false;

    public function mount(?string $slug = null): void
    {
        $agent = $slug !== null
            ? Agent::query()->where('slug', $slug)->first()
            : (request()->query('ref') ? Agent::query()->where('code', mb_strtoupper((string) request()->query('ref')))->first() : null);

        if ($agent && $agent->user()->where('status', UserStatus::Active)->exists()) {
            $this->agentId = $agent->id;
            $this->countClick($agent);
        }
    }

    /** One click per visitor per agent per day; previews from the Portal Ejen are not counted. */
    private function countClick(Agent $agent): void
    {
        if (request()->boolean('pratonton')) {
            return;
        }

        $key = 'agent-click.'.$agent->id.'.'.today()->toDateString();

        if (! session()->has($key)) {
            session()->put($key, true);
            $agent->recordClick();
        }
    }

    #[Computed]
    public function agent(): ?Agent
    {
        return $this->agentId ? Agent::query()->with('user')->find($this->agentId) : null;
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function products(): Collection
    {
        return Product::query()
            ->with(['package', 'country'])
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->where('service', $this->service)
            ->whereHas('package')
            ->orderBy('price_sen')
            ->get();
    }

    #[Computed]
    public function product(): ?Product
    {
        return $this->productId
            ? Product::query()->with(['package', 'country'])->where('is_active', true)->where('stock', '>', 0)->find($this->productId)
            : null;
    }

    public function start(): void
    {
        $this->started = true;
    }

    public function setService(string $service): void
    {
        $this->service = Service::tryFrom($service)->value ?? 'qurban';
        unset($this->products);
    }

    public function pick(int $id): void
    {
        $this->productId = Product::query()->where('is_active', true)->where('stock', '>', 0)->whereKey($id)->value('id');
        unset($this->product);
    }

    public function toStep(int $step): void
    {
        if ($step >= 2 && ! $this->product) {
            $this->addError('productId', 'Sila pilih satu pakej.');

            return;
        }

        if ($step >= 3) {
            $this->validateDetails();
        }

        $this->step = max(1, min(3, $step));
    }

    public function addParticipant(): void
    {
        if (count($this->participants) < $this->maxQuantity()) {
            $this->participants[] = '';
        }
    }

    public function removeParticipant(int $index): void
    {
        if ($index > 0 && isset($this->participants[$index])) {
            unset($this->participants[$index]);
            $this->participants = array_values($this->participants);
        }
    }

    /** The first participant follows the customer's name until edited (design names[0] default). */
    public function updatedName(string $value): void
    {
        if (! $this->firstParticipantEdited) {
            $this->participants[0] = $value;
        }
    }

    public function updatedParticipants(mixed $value, string $key): void
    {
        if ($key === '0') {
            $this->firstParticipantEdited = true;
        }
    }

    public function applyPromo(): void
    {
        $code = mb_strtoupper(trim($this->promo));
        $this->promo = $code;
        $this->promoApplied = $code;
    }

    public function quantity(): int
    {
        return max(1, min($this->maxQuantity(), count($this->participants)));
    }

    public function maxQuantity(): int
    {
        return min(self::MAX_PARTICIPANTS, max(1, $this->product->stock ?? self::MAX_PARTICIPANTS));
    }

    public function promoCode(): ?PromoCode
    {
        return $this->promoApplied !== '' ? PromoCode::findUsable($this->promoApplied) : null;
    }

    public function price(): ?PriceBreakdown
    {
        return $this->product ? app(CalculatePrice::class)->handle($this->product, $this->quantity(), $this->promoCode()) : null;
    }

    public function pay(CreatePublicBooking $create, StartBookingPayment $start): mixed
    {
        $key = 'public-booking:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 6)) {
            $this->addError('pay', 'Terlalu banyak tempahan dalam masa singkat. Sila cuba sebentar lagi.');

            return null;
        }

        $product = $this->product;

        if (! $product) {
            $this->step = 1;

            return null;
        }

        $this->validateDetails();
        $method = PaymentMethod::tryFrom($this->payType) ?? PaymentMethod::Fpx;

        $this->validate([
            'payType' => ['required', Rule::enum(PaymentMethod::class)],
            'akad' => ['accepted'],
            'proof' => $method === PaymentMethod::Fpx ? ['nullable'] : ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'akad.accepted' => 'Sila sahkan lafaz akad sebelum membuat bayaran.',
            'proof.required' => 'Sila muat naik bukti bayaran.',
            'proof.mimes' => 'Bukti bayaran mesti JPG, PNG atau PDF.',
            'proof.max' => 'Saiz bukti bayaran maksimum 5 MB.',
        ]);

        RateLimiter::hit($key, 600);

        try {
            $order = $create->handle(
                [
                    'name' => trim($this->name), 'phone' => trim($this->phone), 'email' => mb_strtolower(trim($this->email)),
                    'address' => trim($this->address), 'postcode' => trim($this->postcode) ?: null,
                    'city' => trim($this->city) ?: null, 'state' => $this->state,
                ],
                $product,
                $this->quantity(),
                array_map('trim', $this->participants),
                $this->promoCode()?->code,
                $method,
                $this->proof instanceof UploadedFile ? $this->proof : null,
                $this->agent,
            );
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());

            return null;
        }

        if ($method !== PaymentMethod::Fpx) {
            return $this->redirectRoute('booking.receipt', ['token' => $order->tracking_token]);
        }

        try {
            $tx = $start->handle($order);
        } catch (ValidationException) {
            // CHIP unavailable: the order is kept and can be paid from the receipt page.
            return $this->redirectRoute('booking.receipt', ['token' => $order->tracking_token]);
        }

        return $this->redirect((string) $tx->checkout_url);
    }

    private function validateDetails(): void
    {
        $max = $this->maxQuantity();

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s]{9,20}$/'],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'postcode' => ['nullable', 'regex:/^\d{5}$/'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['required', Rule::in(self::STATES)],
            'participants' => ['required', 'array', 'min:1', 'max:'.$max],
            'participants.*' => ['required', 'string', 'max:120'],
        ], [
            'phone.regex' => 'No. telefon tidak sah.',
            'postcode.regex' => 'Poskod mesti 5 digit.',
            'participants.max' => "Maksimum {$max} peserta bagi satu tempahan.",
            'participants.*.required' => 'Sila isi nama setiap peserta.',
        ], [
            'name' => 'nama penuh', 'phone' => 'no. telefon', 'email' => 'emel', 'address' => 'alamat',
            'postcode' => 'poskod', 'city' => 'bandar', 'state' => 'negeri',
        ]);
    }

    public function render(): mixed
    {
        return view('livewire.public.booking', [
            'price' => $this->price(),
            'promoError' => $this->promoApplied !== '' && ! $this->promoCode(),
            'bank' => app(Settings::class)->group('company'),
        ]);
    }
}
