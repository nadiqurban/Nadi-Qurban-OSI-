<?php

namespace App\Livewire\Forms;

use App\Actions\Installments\CreatePlan;
use App\Actions\Pricing\CalculatePrice;
use App\Actions\Pricing\PriceBreakdown;
use App\Enums\InstallmentPayMethod;
use App\Enums\MalaysianState;
use App\Models\Product;
use App\Models\PromoCode;
use App\Support\Settings;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

/** "Tempahan Baharu (Ansuran)" modal (Bayaran Ansuran.dc.html showNew). */
class InstallmentPlanForm extends Form
{
    public string $name = '';

    public string $address = '';

    public string $postcode = '';

    public string $city = '';

    public string $state = 'Selangor';

    public string $phone = '';

    public string $email = '';

    public ?int $productId = null;

    public ?int $countryId = null;

    public string $quantity = '1';

    public string $year = '';

    public string $implementationDate = '';

    public string $months = '6';

    public string $deposit = '';

    public string $promo = '';

    public string $paymentMethod = 'fpx_auto';

    /** @var array<int, string> */
    public array $participants = [];

    /** @var TemporaryUploadedFile|null */
    public $proof = null;

    public function resetForm(): void
    {
        $this->reset();
        $this->resetValidation();
        $this->year = (string) app(Settings::class)->get('season.year', now()->year);
    }

    public function product(): ?Product
    {
        return $this->productId ? Product::query()->with(['package', 'country'])->find($this->productId) : null;
    }

    public function quantityInt(): int
    {
        return max(1, min(700, (int) $this->quantity));
    }

    public function monthsInt(): int
    {
        return in_array((int) $this->months, CreatePlan::TENURES, true) ? (int) $this->months : 6;
    }

    public function depositSen(): int
    {
        return max(0, (int) round((float) preg_replace('/[^0-9.]/', '', $this->deposit) * 100));
    }

    /** Harga − Deposit − Diskaun = Baki Ansuran (live summary). */
    public function breakdown(): ?PriceBreakdown
    {
        $product = $this->product();

        return $product ? app(CalculatePrice::class)->handle($product, $this->quantityInt(), PromoCode::findUsable($this->promo), $this->depositSen()) : null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:300'],
            'postcode' => ['nullable', 'digits:5'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['required', Rule::enum(MalaysianState::class)],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{9,20}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'productId' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')->where('is_active', true)],
            'countryId' => ['nullable', 'integer', Rule::exists('countries', 'id')],
            'quantity' => ['required', 'integer', 'min:1', 'max:700'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'implementationDate' => ['nullable', 'date'],
            'months' => ['required', Rule::in(array_map('strval', CreatePlan::TENURES))],
            'deposit' => ['nullable', 'regex:/^\d+(\.\d{1,2})?$/'],
            'promo' => ['nullable', 'string', 'max:30'],
            'paymentMethod' => ['required', Rule::enum(InstallmentPayMethod::class)],
            'participants.*' => ['nullable', 'string', 'max:150'],
            'proof' => [$this->paymentMethod === InstallmentPayMethod::Manual->value ? 'required' : 'nullable', 'file', 'mimes:png,pdf', 'max:10240'],
        ];
    }

    /** @return array<string, string> */
    public function validationAttributes(): array
    {
        return [
            'name' => 'nama pelanggan', 'address' => 'alamat', 'postcode' => 'poskod', 'city' => 'bandar',
            'state' => 'negeri', 'phone' => 'no. telefon', 'email' => 'emel', 'productId' => 'produk', 'countryId' => 'negara',
            'quantity' => 'kuantiti', 'year' => 'tahun pelaksanaan', 'implementationDate' => 'tarikh pelaksanaan',
            'months' => 'tempoh ansuran', 'deposit' => 'deposit', 'promo' => 'kod promosi',
            'paymentMethod' => 'kaedah bayaran', 'participants.*' => 'nama peserta', 'proof' => 'resit bank',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'phone.regex' => 'No. telefon tidak sah (cth. 012-3456789).',
            'deposit.regex' => 'Deposit mesti nombor (cth. 500).',
            'proof.required' => 'Muat naik resit bank untuk bayaran manual.',
            'proof.mimes' => 'Resit mesti PNG atau PDF.',
        ];
    }
}
