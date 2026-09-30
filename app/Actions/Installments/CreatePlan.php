<?php

namespace App\Actions\Installments;

use App\Actions\Pricing\CalculatePrice;
use App\Enums\InstallmentPayMethod;
use App\Enums\InstallmentPlanStatus;
use App\Enums\InstallmentStatus;
use App\Enums\Severity;
use App\Models\Customer;
use App\Models\InstallmentPlan;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\User;
use App\Support\Audit;
use App\Support\Sequence;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * "Simpan Pelan": reserves the order number, snapshots the price (product × qty −
 * promo), deducts the deposit and creates the monthly schedule (whole-ringgit
 * instalments, remainder in the last month) due monthly from today.
 */
class CreatePlan
{
    public const TENURES = [3, 6, 9, 12];

    public function __construct(private readonly CalculatePrice $pricing) {}

    /**
     * @param  array{name: string, phone: string, email?: ?string, address?: ?string, postcode?: ?string, city?: ?string, state?: ?string}  $customer
     * @param  array{product_id: int, quantity: int, year: int, implementation_date?: ?string, months: int, deposit_sen: int, promo_code?: ?string, payment_method: string, country_id?: ?int, start_date?: ?string}  $plan
     * @param  list<string>  $participants
     */
    public function handle(array $customer, array $plan, array $participants, ?UploadedFile $depositProof, ?User $actor): InstallmentPlan
    {
        if (! in_array($plan['months'], self::TENURES, true)) {
            throw ValidationException::withMessages(['months' => 'Tempoh ansuran mesti 3, 6, 9 atau 12 bulan.']);
        }

        return DB::transaction(function () use ($customer, $plan, $participants, $depositProof, $actor) {
            $product = Product::query()->with('package')->findOrFail($plan['product_id']);

            if (! $product->is_active) {
                throw ValidationException::withMessages(['productId' => 'Produk ini tidak aktif.']);
            }

            $promo = null;

            if (! empty($plan['promo_code'])) {
                $promo = PromoCode::findUsable($plan['promo_code']) ?? throw ValidationException::withMessages(['promo' => 'Kod promosi tidak sah, telah tamat atau mencapai had.']);
            }

            $price = $this->pricing->handle($product, $plan['quantity'], $promo, $plan['deposit_sen']);

            if ($plan['deposit_sen'] > $price->totalSen) {
                throw ValidationException::withMessages(['deposit' => 'Deposit melebihi jumlah harga.']);
            }

            $schedule = $price->instalments($plan['months']);

            if ($price->balanceSen() <= 0) {
                throw ValidationException::withMessages(['deposit' => 'Tiada baki untuk diansurkan.']);
            }

            $client = Customer::resolve($customer);
            $seq = Sequence::next('order', 1249);
            $method = InstallmentPayMethod::from($plan['payment_method']);

            $model = InstallmentPlan::query()->create([
                'order_no' => sprintf('NQ-%s-%s-%06d', $product->service->code(), $product->animal->code(), $seq),
                'customer_id' => $client->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'service' => $product->service,
                'animal' => $product->animal,
                'package_name' => $product->package->name,
                'country_id' => $plan['country_id'] ?? $product->country_id,
                'quantity' => $plan['quantity'],
                'participant_names' => $this->names($participants, $plan['quantity'], $client->name),
                'year' => $plan['year'],
                'implementation_date' => $plan['implementation_date'] ?? null,
                'unit_price_sen' => $price->unitPriceSen,
                'subtotal_sen' => $price->subtotalSen,
                'discount_sen' => $price->discountSen,
                'total_sen' => $price->totalSen,
                'deposit_sen' => $price->depositSen,
                'promo_code_id' => $promo?->id,
                'promo_code' => $price->promoCode,
                'months' => $plan['months'],
                'monthly_sen' => $schedule[0],
                'payment_method' => $method,
                'status' => InstallmentPlanStatus::Ongoing,
                'pay_token' => Str::random(48),
                'created_by' => $actor?->id,
            ]);

            $start = Carbon::parse($plan['start_date'] ?? today());

            foreach ($schedule as $i => $amount) {
                $model->installments()->create([
                    'seq' => $i + 1,
                    'due_date' => $start->copy()->addMonthsNoOverflow($i),
                    'amount_sen' => $amount,
                    'status' => InstallmentStatus::Unpaid,
                ]);
            }

            // The plan's Order is created without a promo link, so usage is counted here once.
            if ($promo && $price->discountSen > 0) {
                PromoCode::query()->whereKey($promo->id)->incrementEach(['used_count' => 1, 'total_discount_sen' => $price->discountSen]);
            }

            if ($depositProof) {
                $model->addMedia($depositProof)->usingFileName('deposit-'.$model->order_no.'.'.$depositProof->extension())->toMediaCollection('deposit_proof');
            }

            Audit::log('installment.created', "Pelan ansuran {$model->order_no} dicipta ({$model->months} bulan)", $model, Severity::Info,
                ['total' => $price->totalSen, 'deposit' => $price->depositSen], $actor, 'installments');

            return $model;
        });
    }

    /**
     * @param  list<string>  $names
     * @return list<string>
     */
    private function names(array $names, int $quantity, string $customerName): array
    {
        return array_map(
            fn (int $i) => trim((string) ($names[$i] ?? '')) ?: ($i === 0 ? $customerName : ''),
            range(0, $quantity - 1),
        );
    }
}
