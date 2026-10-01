<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $invoice_id
 * @property string $description
 * @property string|null $detail
 * @property int $quantity
 * @property int $unit_price_sen
 * @property int $line_total_sen
 * @property int $position
 */
class InvoiceItem extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'unit_price_sen' => 'integer', 'line_total_sen' => 'integer'];
    }
}
