<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Database\Seeder;

/** Production-safe: inserts default settings only when missing. */
class SettingsSeeder extends Seeder
{
    public function run(Settings $settings): void
    {
        $defaults = Settings::COMPANY_DEFAULTS + [
            'season.year' => '2027',
            'support.phone' => '6011-3763 9921',
            'finance.usd_rate' => '4.70',               // RM per USD for vendor POs (snapshot per PO)
            'support.whatsapp' => '601137639921',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }

        $settings->flush();
    }
}
