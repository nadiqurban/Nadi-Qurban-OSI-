<?php

namespace Database\Seeders;

use App\Actions\Agents\SaveAgent;
use App\Models\Agent;
use App\Models\AgentClick;
use App\Models\Order;
use Illuminate\Database\Seeder;

/**
 * Local/demo only: agent "Aiman Zulkifli" (AZ01, Pengurusan Ejen.dc.html) with a few
 * demo orders credited to his link — one waiting in Pengesahan Bayaran, one verified,
 * one completed — so Portal Ejen and Pengurusan Ejen have data. The login password is
 * SEED_USER_PASSWORD (skipped when unset, like the demo users).
 */
class DemoAgentSeeder extends Seeder
{
    public function run(SaveAgent $save): void
    {
        $password = config('nadi.seed_user_password');
        $admin = SampleDataSeeder::staff();

        if (! $password || ! $admin) {
            $this->command->warn('SEED_USER_PASSWORD tidak ditetapkan — ejen demo tidak dicipta.');

            return;
        }

        $agent = Agent::query()->where('code', 'AZ01')->first() ?? $save->handle(null, [
            'code' => 'AZ01', 'name' => 'Aiman Zulkifli', 'email' => 'aiman@nadiqurban.com', 'phone' => '012-334 5671',
            'password' => (string) $password, 'gender' => 'Lelaki', 'birth_date' => '1992-04-12', 'district' => 'Petaling',
            'state' => 'Selangor', 'bank_name' => 'Maybank', 'bank_account_name' => 'Aiman Zulkifli', 'bank_account_no' => '5623 5782 2681',
        ], $admin);

        Order::query()->with('product')
            ->whereIn('order_no', ['NQ-QB-LE-001249', 'NQ-QB-LE-001252', 'NQ-QB-LE-001241'])
            ->whereNull('agent_id')
            ->get()
            ->each(fn (Order $o) => $o->forceFill([
                'agent_id' => $agent->id,
                'source' => 'public',
                'commission_sen' => ($o->product->commission_sen ?? 0) * $o->quantity,
            ])->save());

        foreach ([[0, 12], [1, 9], [3, 25]] as [$daysAgo, $clicks]) {
            AgentClick::query()->updateOrCreate(['agent_id' => $agent->id, 'date' => today()->subDays($daysAgo)->toDateString()], ['clicks' => $clicks]);
        }
    }
}
