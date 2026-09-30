<?php

namespace App\Livewire\Forms;

use App\Actions\Pricing\CalculatePrice;
use App\Actions\Pricing\PriceBreakdown;
use App\Enums\MalaysianState;
use App\Enums\PaymentMethod;
use App\Models\Product;
use App\Models\PromoCode;
use App\Support\Settings;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

/** "Tempahan Baharu" modal fields (Tempahan & Pelanggan.dc.html new-order view). */
class OrderForm extends Form
{
    public string $name = '';

    public string $address = '';

    public string $postcode = '';

    public string $city = '';

    public string $state = 'Selangor';

    public string $phone = '';

    public string $email = '';

    public ?int $productId = null;

    public string $quantity = '1';

    public string $year = '';

    public string $implementationDate = '';

    public string $promo = '';

    public string $paymentMethod = 'fpx';

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
        return max(1, (int) $this->quantity);
    }

    /** Live summary for the modal (Harga Produk / Diskaun Promosi / Jumlah Keseluruhan). */
    public function breakdown(): ?PriceBreakdown
    {
        $product = $this->product();

        return $product ? app(CalculatePrice::class)->handle($product, $this->quantityInt(), $this->promoModel()) : null;
    }

    public function promoModel(): ?PromoCode
    {
        return PromoCode::findUsable($this->promo);
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
            'quantity' => ['required', 'integer', 'min:1', 'max:700'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'implementationDate' => ['nullable', 'date'],
            'promo' => ['nullable', 'string', 'max:30'],
            'paymentMethod' => ['required', Rule::enum(PaymentMethod::class)],
            'proof' => ['nullable', 'file', 'mimes:png,jpg,jpeg,pdf', 'max:10240'],
        ];
    }

    /** @return array<string, string> */
    public function validationAttributes(): array
    {
        return [
            'name' => 'nama pelanggan', 'address' => 'alamat', 'postcode' => 'poskod', 'city' => 'bandar',
            'state' => 'negeri', 'phone' => 'no. telefon', 'email' => 'emel', 'productId' => 'produk',
            'quantity' => 'kuantiti', 'year' => 'tahun pelaksanaan', 'implementationDate' => 'tarikh pelaksanaan',
            'promo' => 'kod promosi', 'paymentMethod' => 'jenis bayaran', 'proof' => 'bukti bayaran',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'phone.regex' => 'No. telefon tidak sah (cth. 012-3456789).',
            'proof.mimes' => 'Bukti bayaran mesti PNG, JPG atau PDF.',
            'proof.max' => 'Bukti bayaran tidak boleh melebihi 10MB.',
        ];
    }
}
