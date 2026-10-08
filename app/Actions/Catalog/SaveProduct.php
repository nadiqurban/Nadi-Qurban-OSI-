<?php

namespace App\Actions\Catalog;

use App\Enums\Animal;
use App\Enums\Service;
use App\Enums\Severity;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;

class SaveProduct
{
    /**
     * @param  array{name: string, service: string, animal: string, package_id: int, country_id: int, price_sen: int, commission_sen: int, stock: int, unit?: string, description: ?string, is_active: bool}  $data
     */
    public function handle(?Product $product, array $data, User $actor): Product
    {
        return DB::transaction(function () use ($product, $data, $actor) {
            $package = Package::query()->findOrFail($data['package_id']);
            $code = $this->uniqueCode(
                Product::makeCode(Service::from($data['service']), Animal::from($data['animal']), $package),
                $product?->id,
            );

            $isNew = $product === null;
            $product ??= new Product;
            $before = $isNew ? [] : $product->only(['name', 'price_sen', 'commission_sen', 'stock', 'unit', 'is_active', 'country_id', 'package_id']);

            $product->fill($data + ['code' => $code])->save();

            $priceChanged = ! $isNew && $before['price_sen'] !== $product->price_sen;

            Audit::log(
                $isNew ? 'product.created' : 'product.updated',
                $isNew ? "Produk {$product->name} dicipta" : "Produk {$product->name} dikemaskini",
                $product,
                $priceChanged ? Severity::Warning : Severity::Info,
                $isNew ? ['price_sen' => $product->price_sen] : ['before' => $before, 'after' => $product->only(array_keys($before))],
                $actor,
                'catalog',
            );

            return $product;
        });
    }

    /** Several products may share service/animal/package (different countries): suffix -2, -3 … */
    private function uniqueCode(string $base, ?int $ignoreId): string
    {
        $code = $base;
        $n = 2;

        while (Product::withTrashed()->where('code', $code)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $code = $base.'-'.$n++;
        }

        return $code;
    }
}
