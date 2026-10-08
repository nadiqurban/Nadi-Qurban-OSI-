<?php

namespace App\Console\Commands;

use App\Enums\Animal;
use App\Enums\OrderStage;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Service;
use App\Enums\Severity;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderStageHistory;
use App\Models\Payment;
use App\Models\PaymentGatewayTransaction;
use App\Services\Chip\ChipGateway;
use App\Support\Audit;
use App\Support\DashboardStats;
use App\Support\Settings;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Re-creates public-booking orders that were paid through CHIP but are missing from the
 * database, straight from the CHIP Purchase (customer, product line, amount, paid time).
 * The order keeps its original number; participant 1 = the customer (others to be filled
 * in by staff). Oldest purchase first. Already-present purchases are skipped.
 */
class RestoreChipOrders extends Command
{
    protected $signature = 'nq:restore-chip {purchase* : ID pembelian CHIP (dari portal CHIP)} {--negara= : Negara jika tidak dapat dikesan dari nama produk}';

    protected $description = 'Masukkan semula tempahan awam yang telah dibayar melalui CHIP';

    public function handle(ChipGateway $chip): int
    {
        $purchases = collect((array) $this->argument('purchase'))
            ->map(fn (string $id) => $this->fetch($chip, trim($id)))
            ->filter()
            ->sortBy(fn (array $p) => (int) ($p['created_on'] ?? 0))
            ->values();

        $restored = 0;

        foreach ($purchases as $purchase) {
            $restored += (int) $this->restore($purchase);
        }

        DashboardStats::flush();
        $this->info("Siap: {$restored} tempahan dimasukkan semula.");

        return self::SUCCESS;
    }

    /** @return array<string, mixed>|null */
    private function fetch(ChipGateway $chip, string $id): ?array
    {
        try {
            return $chip->getPurchase($id);
        } catch (RuntimeException $e) {
            $this->error("{$id}: {$e->getMessage()}");

            return null;
        }
    }

    /** @param  array<string, mixed>  $purchase */
    private function restore(array $purchase): bool
    {
        $id = (string) ($purchase['id'] ?? '');
        $reference = (string) ($purchase['reference'] ?? '');
        $line = (string) data_get($purchase, 'purchase.products.0.name', '');

        if (($purchase['status'] ?? '') !== 'paid') {
            $this->warn("{$reference}: status CHIP '{$purchase['status']}' — bukan bayaran berjaya, dilangkau.");

            return false;
        }

        if (! preg_match('/^(.+) — (.+) · (NQ-([A-Z]{2})-([A-Z]{2})-(\d{6}))$/u', $line, $m)) {
            $this->warn("{$reference}: \"{$line}\" bukan tempahan awam (mungkin ansuran), dilangkau.");

            return false;
        }

        [, $productName, $packageName, $orderNo, $svcCode, $aniCode, $seq] = $m;

        if (PaymentGatewayTransaction::query()->where('purchase_id', $id)->exists() || Order::withTrashed()->where('order_no', $orderNo)->exists()) {
            $this->line("{$orderNo}: sudah ada dalam sistem, dilangkau.");

            return false;
        }

        $service = collect(Service::cases())->first(fn (Service $s) => $s->code() === $svcCode);
        $animal = collect(Animal::cases())->first(fn (Animal $a) => $a->code() === $aniCode);
        $country = $this->country($productName.' '.$packageName);

        if (! $service || ! $animal || ! $country) {
            $this->warn("{$orderNo}: negara/servis tidak dapat dikesan dari \"{$productName}\" — guna --negara=NamaNegara.");

            return false;
        }

        $quantity = max(1, (int) (preg_match('/^(\d+)\s*×/u', (string) data_get($purchase, 'purchase.notes', ''), $q) ? $q[1] : 1));
        $total = (int) data_get($purchase, 'purchase.total', data_get($purchase, 'payment.amount', 0));
        $createdAt = Carbon::createFromTimestamp((int) ($purchase['created_on'] ?? time()), config('app.timezone'));
        $paidAt = Carbon::createFromTimestamp((int) (data_get($purchase, 'payment.paid_on') ?: $purchase['updated_on'] ?? time()), config('app.timezone'));
        $channel = 'CHIP · '.Str::upper((string) data_get($purchase, 'transaction_data.payment_method', 'Online'));

        DB::transaction(function () use ($purchase, $id, $reference, $productName, $packageName, $orderNo, $seq, $service, $animal, $country, $quantity, $total, $createdAt, $paidAt, $channel) {
            $client = (array) ($purchase['client'] ?? []);
            $phone = (string) preg_replace('/^\+?60/', '0', (string) preg_replace('/[^\d+]/', '', (string) ($client['phone'] ?? '')));

            $customer = Customer::resolve([
                'name' => (string) ($client['full_name'] ?? 'Pelanggan'),
                'phone' => $phone !== '' ? $phone : '-',
                'email' => str_starts_with((string) ($client['email'] ?? ''), 'tiada-emel@') ? null : ($client['email'] ?? null),
            ]);

            $year = (int) app(Settings::class)->get('season.year', $createdAt->year);

            $order = Order::query()->create([
                'order_no' => $orderNo,
                'tracking_no' => sprintf('NQT-%d-%s', $year, $seq),
                'tracking_token' => Str::random(40),
                'customer_id' => $customer->id,
                'product_id' => null,
                'product_name' => $productName,
                'service' => $service,
                'animal' => $animal,
                'package_name' => $packageName,
                'country_id' => $country->id,
                'quantity' => $quantity,
                'year' => $year,
                'unit_price_sen' => intdiv($total, $quantity),
                'subtotal_sen' => $total,
                'discount_sen' => 0,
                'total_sen' => $total,
                'payment_method' => PaymentMethod::Fpx,
                'status' => OrderStatus::InProgress,
                'stage' => OrderStage::PaymentVerified,
                'accepted_at' => $paidAt,
                'source' => 'public',
                'notes' => "Dimasukkan semula daripada bayaran CHIP {$reference}.",
            ]);
            $order->forceFill(['created_at' => $createdAt, 'updated_at' => $paidAt])->save();

            foreach (range(1, $quantity) as $position) {
                $order->participants()->create(['position' => $position, 'name' => $position === 1 ? $customer->name : null]);
            }

            Payment::query()->create([
                'order_id' => $order->id,
                'method' => PaymentMethod::Fpx,
                'channel' => $channel,
                'gateway_status' => 'berjaya',
                'amount_sen' => $total,
                'paid_at' => $paidAt,
                'reference' => $reference,
                'status' => PaymentStatus::Verified,
                'verified_at' => $paidAt,
            ]);

            PaymentGatewayTransaction::query()->create([
                'gateway' => 'chip',
                'reference' => $reference,
                'purchase_id' => $id,
                'order_id' => $order->id,
                'installment_ids' => [],
                'amount_sen' => $total,
                'status' => PaymentGatewayTransaction::PAID,
                'paid_at' => $paidAt,
                'payload' => ['id' => $id, 'status' => 'paid', 'reference' => $reference, 'restored' => true],
            ]);

            OrderStageHistory::query()->create(['order_id' => $order->id, 'stage' => OrderStage::Received, 'note' => 'Tempahan dicipta', 'created_at' => $createdAt]);
            OrderStageHistory::query()->create(['order_id' => $order->id, 'stage' => OrderStage::PaymentVerified, 'note' => 'Bayaran CHIP berjaya', 'created_at' => $paidAt]);

            $this->bumpSequence('order', (int) $seq + 1);
            $this->bumpSequence('gateway-payment', (int) substr($reference, 5) + 1);

            Audit::log('order.restored', "Tempahan {$orderNo} dimasukkan semula daripada CHIP ({$reference})", $order, Severity::Warning,
                ['purchase' => $id, 'total_sen' => $total], null, 'orders');
        });

        $this->info("{$orderNo} · {$createdAt->format('d/m/Y H:i')} · ".data_get($purchase, 'client.full_name').' · RM '.number_format($total / 100, 2).' — dimasukkan semula.');

        return true;
    }

    private function country(string $haystack): ?Country
    {
        $name = (string) $this->option('negara');

        if ($name !== '') {
            return Country::query()->where('name', $name)->first();
        }

        return Country::query()->orderByRaw('LENGTH(name) DESC')->get()
            ->first(fn (Country $c) => Str::contains($haystack, $c->name, ignoreCase: true));
    }

    /** Numbers already given to customers are never handed out again. */
    private function bumpSequence(string $name, int $atLeast): void
    {
        if ($atLeast <= 1) {
            return;
        }

        DB::table('sequences')->insertOrIgnore(['name' => $name, 'next_value' => $atLeast, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('sequences')->where('name', $name)->where('next_value', '<', $atLeast)->update(['next_value' => $atLeast, 'updated_at' => now()]);
    }
}
