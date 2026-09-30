<?php

namespace Database\Seeders;

use App\Enums\Animal;
use App\Enums\DiscountType;
use App\Enums\Service;
use App\Models\Country;
use App\Models\Package;
use App\Models\Product;
use App\Models\PromoCode;
use Illuminate\Database\Seeder;

/**
 * Local/demo only: the 12 products from Produk.dc.html and the 6 promo codes
 * from Kod Promosi.dc.html ("Makkah" products are implemented in Arab Saudi).
 */
class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $country = fn (string $name) => Country::query()->where('name', $name)->value('id');
        $package = fn (string $name) => Package::query()->where('name', $name)->firstOrFail();

        // [name, service, animal, package, country, price RM, description, stock]
        $products = [
            ['Qurban Lembu Uganda', Service::Qurban, Animal::Cow, 'Delima', 'Uganda', 3500, '1 bahagian penuh, sijil + laporan video', 48],
            ['Qurban Kambing Nigeria', Service::Qurban, Animal::Goat, 'Topaz', 'Nigeria', 1200, 'Seekor kambing penuh + laporan', 32],
            ['Qurban Unta Somalia', Service::Qurban, Animal::Camel, 'Nilam', 'Somalia', 4900, '1/7 bahagian unta, sijil + hadiah', 14],
            ['Aqiqah Lembu Malaysia', Service::Aqiqah, Animal::Cow, 'Delima', 'Malaysia', 3200, '1 lembu penuh + agihan daging', 20],
            ['Aqiqah Kambing Malaysia', Service::Aqiqah, Animal::Goat, 'Mutiara', 'Malaysia', 850, 'Aqiqah lengkap + agihan daging', 60],
            ['Aqiqah Unta Makkah', Service::Aqiqah, Animal::Camel, 'Nilam', 'Arab Saudi', 4700, '1/7 bahagian unta aqiqah', 8],
            ['Dam Lembu Makkah', Service::Dam, Animal::Cow, 'Zamrud', 'Arab Saudi', 3000, 'Dam berkongsi haji/umrah', 12],
            ['Dam Kambing Makkah', Service::Dam, Animal::Goat, 'Delima', 'Arab Saudi', 720, 'Bayaran dam haji/umrah di Makkah', 40],
            ['Dam Unta Makkah', Service::Dam, Animal::Camel, 'Topaz', 'Arab Saudi', 4600, '1/7 bahagian unta dam', 6],
            ['Nazar Lembu Chad', Service::Nazar, Animal::Cow, 'Delima', 'Chad', 3400, 'Pelaksanaan nazar + laporan', 10],
            ['Nazar Kambing Chad', Service::Nazar, Animal::Goat, 'Topaz', 'Chad', 1100, 'Seekor kambing nazar penuh', 18],
            ['Nazar Unta Sudan', Service::Nazar, Animal::Camel, 'Nilam', 'Sudan', 4800, '1/7 bahagian unta nazar', 5],
        ];

        foreach ($products as [$name, $service, $animal, $pkg, $countryName, $price, $desc, $stock]) {
            $packageModel = $package($pkg);

            Product::query()->updateOrCreate(['name' => $name], [
                'code' => Product::makeCode($service, $animal, $packageModel),
                'service' => $service,
                'animal' => $animal,
                'package_id' => $packageModel->id,
                'country_id' => $country($countryName),
                'price_sen' => $price * 100,
                'stock' => $stock,
                'description' => $desc,
                'is_active' => true,
            ]);
        }

        // [code, description, type, value (% or RM), used, limit, expires, active, total discount RM]
        $codes = [
            ['AWALQURBAN', 'Diskaun tempahan awal musim', DiscountType::Percent, 10, 312, 500, '2027-03-31', true, 15600],
            ['RAYA50', 'Potongan RM50 setiap lembu', DiscountType::Fixed, 50, 188, 300, '2027-04-30', true, 9400],
            ['AQIQAH15', 'Diskaun pakej Aqiqah', DiscountType::Percent, 15, 96, null, '2027-12-31', true, 4200],
            ['GROUP7', 'Diskaun kumpulan 7 bahagian', DiscountType::Percent, 8, 241, 400, '2027-05-15', true, 5800],
            ['EARLYBIRD26', 'Promo musim lepas', DiscountType::Percent, 12, 420, 420, '2026-12-31', false, 2500],
            ['STAFFNQ', 'Diskaun kakitangan', DiscountType::Percent, 20, 27, 50, '2026-12-31', false, 500],
        ];

        foreach ($codes as [$code, $desc, $type, $value, $used, $limit, $expires, $active, $discount]) {
            PromoCode::query()->updateOrCreate(['code' => $code], [
                'description' => $desc,
                'type' => $type,
                'value' => $type === DiscountType::Fixed ? $value * 100 : $value,
                'used_count' => $used,
                'usage_limit' => $limit,
                'expires_at' => $expires,
                'is_active' => $active,
                'total_discount_sen' => $discount * 100,
            ]);
        }
    }
}
