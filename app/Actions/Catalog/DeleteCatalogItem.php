<?php

namespace App\Actions\Catalog;

use App\Enums\Severity;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\User;
use App\Support\Audit;

/** Soft delete a product or promo code ("Buang"), recorded as Amaran. */
class DeleteCatalogItem
{
    public function handle(Product|PromoCode $item, User $actor): void
    {
        $item->delete();

        [$event, $label] = $item instanceof Product
            ? ['product.deleted', "Produk {$item->name} dibuang"]
            : ['promo.deleted', "Kod promosi {$item->code} dibuang"];

        Audit::log($event, $label, $item, Severity::Warning, causer: $actor, logName: 'catalog');
    }
}
