<?php

namespace Database\Seeders;

use App\Enums\VendorStatus;
use App\Models\Country;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

/** Local/demo only: the six vendors from Vendor.dc.html (SP 001 → SP 006). */
class DemoVendorSeeder extends Seeder
{
    public function run(): void
    {
        // [name, company, country, status, animals, phone, email, level, rating]
        $vendors = [
            ['Uganda Charity', 'Uganda Charity Ltd', 'Uganda', VendorStatus::Active, ['Lembu', 'Kambing'], '+256 700 112 233', 'contact@albarakah.ug', 'platinum', 4.9],
            ['Qurban Nusantara', 'Qurban Nusantara Sdn Bhd', 'Malaysia', VendorStatus::Active, ['Lembu', 'Kambing'], '03-5544 7788', 'admin@qurbannusantara.my', 'gold', 4.8],
            ['Sahara Cattle Co.', 'Sahara Cattle Trading Co', 'Chad', VendorStatus::Active, ['Lembu', 'Unta'], '+235 63 220 118', 'sales@saharacattle.td', 'gold', 4.6],
            ['Kano Farms', 'Kano Farms Ltd', 'Nigeria', VendorStatus::Pending, ['Lembu', 'Kambing'], '+234 803 556 900', 'info@kanofarms.ng', 'silver', 4.4],
            ['Gujarat Livestock', 'Gujarat Livestock Pvt Ltd', 'India', VendorStatus::Active, ['Kambing', 'Lembu'], '+91 79 4455 1200', 'hello@gujaratlive.in', 'silver', 4.5],
            ['Riyadh Camel Trading', 'Riyadh Camel Trading Est', 'Arab Saudi', VendorStatus::Suspended, ['Unta', 'Kambing'], '+966 11 220 3344', 'ops@riyadhcamel.sa', 'bronze', 3.9],
        ];

        foreach ($vendors as $i => [$name, $company, $country, $status, $animals, $phone, $email, $level, $rating]) {
            Vendor::query()->updateOrCreate(['code' => sprintf('SP %03d', $i + 1)], [
                'name' => $name,
                'company' => $company,
                'supplier' => $company,
                'country_id' => Country::query()->where('name', $country)->value('id'),
                'status' => $status,
                'animals' => $animals,
                'phone' => $phone,
                'email' => $email,
                'level' => $level,
                'rating' => $rating,
                'rank' => $i + 1,
            ]);
        }

        // The demo Vendor PIC belongs to Uganda Charity.
        User::query()->where('email', 'vendor@albarakah.ug')
            ->update(['vendor_id' => Vendor::query()->where('code', 'SP 001')->value('id')]);
    }
}
