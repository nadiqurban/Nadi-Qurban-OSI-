<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Sebut harga (Kewangan "Quotation").
 *
 * @property int $id
 * @property string $quotation_no
 * @property string $customer_name
 * @property string|null $customer_phone
 * @property string|null $customer_email
 * @property string|null $customer_address
 * @property array<string, string> $company
 * @property list<array{description: string, quantity: int, unit_price_sen: int}> $items
 * @property int $total_sen
 * @property Carbon|null $valid_until
 * @property string|null $notes
 * @property Carbon $created_at
 */
class Quotation extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['company' => 'array', 'items' => 'array', 'valid_until' => 'date', 'total_sen' => 'integer'];
    }

    /** View model shared with the draft preview (pdf.partials.finance-doc-a4). */
    public function toDocument(): object
    {
        return (object) [
            'type' => 'quote',
            'number' => $this->quotation_no,
            'company' => $this->company,
            'name' => $this->customer_name,
            'phone' => $this->customer_phone,
            'email' => $this->customer_email,
            'address' => $this->customer_address,
            'order' => null,
            'date' => $this->created_at,
            'due' => $this->valid_until,
            'note' => $this->notes,
            'items' => $this->items,
            'total_sen' => $this->total_sen,
        ];
    }
}
