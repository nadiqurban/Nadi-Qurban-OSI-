<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Package;
use Illuminate\Database\Seeder;

/**
 * Production-safe: implementation countries (every country that appears in the
 * design files) and the five package tiers. Idempotent.
 */
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['Malaysia', 'MY', '🇲🇾'],
            ['Arab Saudi', 'SA', '🇸🇦'],
            ['Uganda', 'UG', '🇺🇬'],
            ['Chad', 'TD', '🇹🇩'],
            ['Nigeria', 'NG', '🇳🇬'],
            ['India', 'IN', '🇮🇳'],
            ['Burkina Faso', 'BF', '🇧🇫'],
            ['Somalia', 'SO', '🇸🇴'],
            ['Sudan', 'SD', '🇸🇩'],
            ['Indonesia', 'ID', '🇮🇩'],
            ['Thailand', 'TH', '🇹🇭'],
            ['Bangladesh', 'BD', '🇧🇩'],
            ['Kemboja', 'KH', '🇰🇭'],
            ['Palestin', 'PS', '🇵🇸'],
        ];

        foreach ($countries as $i => [$name, $iso2, $flag]) {
            Country::query()->updateOrCreate(['iso2' => $iso2], ['name' => $name, 'flag' => $flag, 'sort' => $i + 1]);
        }

        $packages = [['Delima', 'DEL'], ['Zamrud', 'ZAM'], ['Topaz', 'TOP'], ['Nilam', 'NIL'], ['Mutiara', 'MUT']];

        foreach ($packages as $i => [$name, $code]) {
            Package::query()->updateOrCreate(['code' => $code], ['name' => $name, 'sort' => $i + 1]);
        }
    }
}
