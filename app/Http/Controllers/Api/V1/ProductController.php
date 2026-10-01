<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** GET /api/v1/products — active products (catalogue for partner checkouts). */
class ProductController
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()->with(['package', 'country'])->where('is_active', true)
            ->when($request->query('service'), fn ($q, $s) => $q->where('service', $s))
            ->when($request->query('country'), fn ($q, $iso) => $q->whereHas('country', fn ($c) => $c->where('iso2', strtoupper((string) $iso))))
            ->orderBy('code')->get();

        return response()->json([
            'data' => $products->map(fn (Product $p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'service' => $p->service->value,
                'animal' => $p->animal->value,
                'package' => $p->package->name,
                'country' => ['name' => $p->country->name, 'iso2' => $p->country->iso2],
                'price' => ['amount_sen' => $p->price_sen, 'formatted' => rm($p->price_sen)],
                'in_stock' => $p->stock > 0,
            ])->values(),
        ]);
    }
}
