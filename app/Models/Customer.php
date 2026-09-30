<?php

namespace App\Models;

use App\Support\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $phone
 * @property string|null $email
 * @property string|null $address
 * @property string|null $postcode
 * @property string|null $city
 * @property string|null $state
 */
class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'phone', 'email', 'address', 'postcode', 'city', 'state'];

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            $customer->code ??= 'CUST-'.Sequence::next('customer', 10240);
        });
    }

    /** Normalised phone for matching an existing customer ("012-345 6789" → "0123456789"). */
    public static function normalisePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    /**
     * Find the customer by phone (ignoring formatting) or create one; keeps existing
     * details unless new ones are given.
     *
     * @param  array{name: string, phone: string, email?: ?string, address?: ?string, postcode?: ?string, city?: ?string, state?: ?string}  $data
     */
    public static function resolve(array $data): self
    {
        $customer = self::query()
            ->whereRaw("REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', '') = ?", [self::normalisePhone($data['phone'])])
            ->first() ?? new self;

        $customer->fill(array_filter($data, fn ($v) => $v !== null && $v !== ''))->save();

        return $customer;
    }
}
