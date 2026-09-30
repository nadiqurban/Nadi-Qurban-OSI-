<?php

namespace Database\Seeders;

use App\Enums\VendorLevel;
use App\Enums\VendorStatus;
use App\Models\Country;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorRankHistory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Local/demo only: the six vendors from Vendor.dc.html (SP 001 → SP 006, VND-2010 → 2015). */
class DemoVendorSeeder extends Seeder
{
    public function run(): void
    {
        // [name, company, country, status, animals, phone, email, rank, rating, PIC, bank, account, swift, bank address]
        $vendors = [
            ['Uganda Charity', 'Uganda Charity Ltd', 'Uganda', VendorStatus::Active, ['Lembu', 'Kambing'], '+256 700 112 233', 'contact@albarakah.ug', 9, 4.9, 'Ismail Kato', 'Stanbic Bank Uganda', '9030012345678', 'SBICUGKX', 'Plot 17 Hannington Road, Kampala'],
            ['Qurban Nusantara', 'Qurban Nusantara Sdn Bhd', 'Malaysia', VendorStatus::Active, ['Lembu', 'Kambing'], '03-5544 7788', 'admin@qurbannusantara.my', 8, 4.8, 'Zainab Yusof', 'Maybank Berhad', '512345678901', 'MBBEMYKL', 'Menara Maybank, 100 Jalan Tun Perak, Kuala Lumpur'],
            ['Sahara Cattle Co.', 'Sahara Cattle Trading Co', 'Chad', VendorStatus::Active, ['Lembu', 'Unta'], '+235 63 220 118', 'sales@saharacattle.td', 7, 4.6, 'Idriss Deby', 'Ecobank Chad', 'TD5300114455', 'ECOCTDND', 'Avenue Charles de Gaulle, N’Djamena'],
            ['Kano Farms', 'Kano Farms Ltd', 'Nigeria', VendorStatus::Pending, ['Lembu', 'Kambing'], '+234 803 556 900', 'info@kanofarms.ng', 6, 4.4, 'Musa Bello', 'First Bank Nigeria', '3088776655', 'FBNINGLA', '35 Marina, Lagos'],
            ['Gujarat Livestock', 'Gujarat Livestock Pvt Ltd', 'India', VendorStatus::Active, ['Kambing', 'Lembu'], '+91 79 4455 1200', 'hello@gujaratlive.in', 5, 4.5, 'Rakesh Patel', 'HDFC Bank', '50100234567890', 'HDFCINBB', 'Ashram Road, Ahmedabad'],
            ['Riyadh Camel Trading', 'Riyadh Camel Trading Est', 'Arab Saudi', VendorStatus::Suspended, ['Unta', 'Kambing'], '+966 11 220 3344', 'ops@riyadhcamel.sa', 3, 3.9, 'Fahad Al-Otaibi', 'Al Rajhi Bank', 'SA0380000000608010167519', 'RJHISARI', 'King Fahd Road, Riyadh'],
        ];

        foreach ($vendors as $i => [$name, $company, $country, $status, $animals, $phone, $email, $rank, $rating, $pic, $bank, $account, $swift, $bankAddress]) {
            $vendor = Vendor::query()->updateOrCreate(['code' => sprintf('SP %03d', $i + 1)], [
                'vendor_no' => 'VND-'.(2010 + $i),
                'name' => $name,
                'company' => $company,
                'supplier' => $pic,
                'country_id' => Country::query()->where('name', $country)->value('id'),
                'status' => $status,
                'animals' => $animals,
                'phone' => $phone,
                'email' => $email,
                'pic_name' => $pic,
                'level' => VendorLevel::fromRank($rank),
                'rating' => $rating,
                'rank' => $rank,
                'bank_name' => $bank,
                'bank_account' => $account,
                'bank_holder' => $company,
                'swift' => $swift,
                'bank_address' => $bankAddress,
            ]);

            if (! $vendor->rankHistories()->exists()) {
                VendorRankHistory::query()->create(['vendor_id' => $vendor->id, 'from_rank' => null, 'to_rank' => max(1, $rank - 2), 'note' => 'Ranking awal ditetapkan', 'created_at' => now()->subYears(2)]);
                VendorRankHistory::query()->create(['vendor_id' => $vendor->id, 'from_rank' => max(1, $rank - 2), 'to_rank' => max(1, $rank - 1), 'created_at' => now()->subMonths(4)]);
                VendorRankHistory::query()->create(['vendor_id' => $vendor->id, 'from_rank' => max(1, $rank - 1), 'to_rank' => $rank, 'created_at' => now()->subMonth()]);
            }
        }

        DB::table('sequences')->updateOrInsert(['name' => 'vendor'], ['next_value' => 2010 + count($vendors), 'created_at' => now(), 'updated_at' => now()]);

        // The demo Vendor PIC belongs to Uganda Charity.
        User::query()->where('email', 'vendor@albarakah.ug')
            ->update(['vendor_id' => Vendor::query()->where('code', 'SP 001')->value('id')]);
    }
}
