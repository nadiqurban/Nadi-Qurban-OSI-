<?php

namespace Database\Seeders;

use App\Enums\LeadStage;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Sales CRM demo leads (Sales CRM.dc.html `columns`) + sample activity trail. */
class DemoCrmSeeder extends Seeder
{
    public function run(): void
    {
        $rahim = User::query()->where('email', 'rahim@nadiqurban.com')->first();
        $hidayah = User::query()->where('email', 'hidayah@nadiqurban.com')->first();
        $owners = ['RS' => $rahim?->id, 'NA' => $hidayah?->id];

        // [name, company, service, RM, owner, days in stage, phone]
        $columns = [
            LeadStage::New->value => [
                ['Masjid Al-Hidayah', 'Jawatankuasa Masjid', 'Korporat', 28000, 'RS', 2],
                ['Ahmad Zaki bin Hassan', 'Individu', 'Qurban', 2450, 'RS', 1],
                ['Syarikat Berkat Sdn Bhd', 'Berkat Group', 'Korporat', 15000, 'NA', 3],
            ],
            LeadStage::Contacted->value => [
                ['Nurul Aina binti Rahim', 'Individu', 'Aqiqah', 1700, 'RS', 4],
                ['Koperasi Guru Selangor', 'KGS Berhad', 'Korporat', 22000, 'NA', 5],
            ],
            LeadStage::Negotiation->value => [
                ['Mohd Firdaus bin Omar', 'Individu', 'Dam', 5200, 'RS', 6],
                ['Surau An-Nur', 'JK Surau', 'Korporat', 18500, 'RS', 3],
            ],
            LeadStage::Proposal->value => [
                ['Yayasan Ikhlas', 'NGO', 'Korporat', 32000, 'NA', 7],
                ['Siti Khadijah bt Yusof', 'Individu', 'Qurban', 4200, 'RS', 2],
            ],
            LeadStage::Closed->value => [
                ['Zulhilmi bin Abdullah', 'Individu', 'Qurban', 3900, 'RS', 1],
                ['Persatuan Belia Damai', 'PBD', 'Korporat', 26000, 'NA', 4],
            ],
        ];
        if (SampleDataSeeder::$active) {
            // One lead at "Cadangan" with the full activity trail.
            $columns = [LeadStage::Proposal->value => array_slice($columns[LeadStage::Proposal->value], 1, 1)];
            $owners['RS'] ??= SampleDataSeeder::staff()?->id;
        }

        $sources = ['WhatsApp Campaign', 'Facebook Ads', 'Rujukan', 'Laman Web'];

        DB::transaction(function () use ($columns, $owners, $sources) {
            $seq = 5040;
            foreach ($columns as $stage => $rows) {
                foreach ($rows as $pos => [$name, $company, $service, $rm, $owner, $days]) {
                    $no = 'LEAD-'.$seq;
                    $changed = now()->subDays($days)->subHours(2);
                    $created = $changed->copy()->subDays(3 + $seq % 5);

                    $lead = Lead::query()->updateOrCreate(['lead_no' => $no], [
                        'name' => $name,
                        'company' => $company,
                        'phone' => '01'.(2 + $seq % 7).'-'.(3456000 + $seq),
                        'email' => strtolower(preg_replace('/[^a-z]+/i', '.', explode(' bin', explode(' binti', $name)[0])[0])).'@email.com',
                        'service' => $service,
                        'package' => 'Delima',
                        'value_sen' => $rm * 100,
                        'stage' => $stage,
                        'position' => $pos,
                        'source' => $sources[$seq % 4],
                        'participants' => $service === 'Korporat' ? 12 + $seq % 20 : 1 + $seq % 3,
                        'owner_id' => $owners[$owner],
                        'stage_changed_at' => $changed,
                        'closed_at' => $stage === LeadStage::Closed->value ? $changed : null,
                        'created_at' => $created,
                    ]);

                    $lead->activities()->delete();
                    $who = $owners[$owner];
                    $acts = [
                        ['created', 'Lead dicipta', 'Lead masuk melalui kempen '.$lead->source.' musim Qurban 2027.', null, 'Sistem', $created],
                        ['whatsapp', 'Mesej WhatsApp', 'Prospek bertanya tentang tarikh pelaksanaan di Arab Saudi.', null, 'Auto-CRM', $created->copy()->addDay()],
                    ];
                    if (LeadStage::from($stage)->step() >= 3) {
                        $acts[] = ['email', 'Sebut harga dihantar', 'Emel sebut harga '.rm($rm * 100).' termasuk sijil & video pelaksanaan.', $who, null, $changed->copy()->subDay()];
                        $acts[] = ['call', 'Panggilan susulan', 'Berbincang pakej '.$service.' untuk '.$lead->participants.' peserta. Prospek berminat pakej premium.', $who, null, $changed];
                    }
                    foreach ($acts as [$type, $title, $desc, $userId, $label, $at]) {
                        $lead->activities()->create(['type' => $type, 'title' => $title, 'description' => $desc, 'user_id' => $userId, 'actor_label' => $label, 'created_at' => $at]);
                    }

                    $seq++;
                }
            }

            DB::table('sequences')->updateOrInsert(['name' => 'lead'], ['next_value' => $seq, 'updated_at' => now(), 'created_at' => now()]);
        });
    }
}
