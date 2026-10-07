<?php

namespace App\Models;

use App\Enums\Animal;
use App\Enums\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Catalogue item — the single source of price for orders and instalment plans (PRD §12.4).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property Service $service
 * @property Animal $animal
 * @property int $package_id
 * @property int $country_id
 * @property int $price_sen
 * @property int $commission_sen agent commission per unit
 * @property int $stock
 * @property string|null $description
 * @property bool $is_active
 * @property-read Package $package
 * @property-read Country $country
 */
class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'service', 'animal', 'package_id', 'country_id',
        'price_sen', 'commission_sen', 'stock', 'description', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'service' => Service::class,
            'animal' => Animal::class,
            'price_sen' => 'integer',
            'commission_sen' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Package, $this> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** "QB-LE-DEL" — service · animal · first 3 letters of the package (Produk.dc.html npCode). */
    public static function makeCode(Service $service, Animal $animal, Package $package): string
    {
        return $service->code().'-'.$animal->code().'-'.$package->code;
    }

    /** Stock colour rule: 0 red, < 20 amber, otherwise green. */
    public function stockClass(): string
    {
        return match (true) {
            $this->stock === 0 => 'text-danger',
            $this->stock < 20 => 'text-warning',
            default => 'text-success',
        };
    }
}
