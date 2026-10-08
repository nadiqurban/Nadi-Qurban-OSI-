<?php

namespace App\Console\Commands;

use App\Enums\Severity;
use App\Models\Customer;
use App\Models\Order;
use App\Support\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Gives one order its own customer when two people booked with the same phone number
 * (customers are matched by phone, so they were merged). The new customer copies the
 * contact details; participant 1 takes the new name.
 */
class SplitCustomer extends Command
{
    protected $signature = 'nq:split-customer {order : No. tempahan} {name : Nama pelanggan sebenar}';

    protected $description = 'Asingkan pelanggan bagi satu tempahan (nombor telefon dikongsi)';

    public function handle(): int
    {
        $order = Order::query()->with('customer')->where('order_no', trim((string) $this->argument('order')))->first();
        $name = trim((string) $this->argument('name'));

        if (! $order || $name === '') {
            $this->error('Tempahan tidak dijumpai atau nama kosong.');

            return self::FAILURE;
        }

        $old = $order->customer;

        if ($old->name === $name) {
            $this->line("{$order->order_no} sudah di bawah {$name}.");

            return self::SUCCESS;
        }

        DB::transaction(function () use ($order, $old, $name) {
            $customer = Customer::query()->create(['name' => $name] + $old->only(['phone', 'email', 'address', 'postcode', 'city', 'state']));

            $order->forceFill(['customer_id' => $customer->id])->save();
            $order->participants()->where('position', 1)->where(fn ($q) => $q->whereNull('name')->orWhere('name', $old->name))->update(['name' => $name]);
            DB::table('installment_plans')->where('order_id', $order->id)->update(['customer_id' => $customer->id]);

            Audit::log('customer.split', "{$order->order_no}: pelanggan diasingkan daripada {$old->name} kepada {$name}", $order, Severity::Info,
                ['from' => $old->id, 'to' => $customer->id], null, 'orders');
        });

        $this->info("{$order->order_no} kini di bawah pelanggan {$name}.");

        return self::SUCCESS;
    }
}
