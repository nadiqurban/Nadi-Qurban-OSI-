<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Customer invoice (Kewangan). Company block is a snapshot (editable per invoice).
 *
 * @property int $id
 * @property string $invoice_no
 * @property int|null $order_id
 * @property string|null $order_no
 * @property string $customer_name
 * @property string|null $customer_phone
 * @property string|null $customer_email
 * @property string|null $customer_address
 * @property array<string, string> $company
 * @property int $subtotal_sen
 * @property int $tax_sen
 * @property int $total_sen
 * @property int $paid_sen
 * @property Carbon $issue_date
 * @property Carbon $due_date
 * @property InvoiceStatus $status
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property-read Order|null $order
 * @property-read Collection<int, InvoiceItem> $items
 * @property-read Collection<int, InvoicePayment> $payments
 */
class Invoice extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'company' => 'array',
            'status' => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal_sen' => 'integer',
            'tax_sen' => 'integer',
            'total_sen' => 'integer',
            'paid_sen' => 'integer',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    /** @return HasMany<InvoiceItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position');
    }

    /** @return HasMany<InvoicePayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->orderBy('paid_at');
    }

    public function balanceSen(): int
    {
        return max(0, $this->total_sen - $this->paid_sen);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('status', '!=', InvoiceStatus::Paid);
    }

    /** View model shared with the draft preview (pdf.partials.finance-doc-a4). */
    public function toDocument(): object
    {
        return (object) [
            'type' => 'invoice',
            'number' => $this->invoice_no,
            'company' => $this->company,
            'name' => $this->customer_name,
            'phone' => $this->customer_phone,
            'email' => $this->customer_email,
            'address' => $this->customer_address,
            'order' => $this->order_no,
            'date' => $this->issue_date,
            'due' => $this->due_date,
            'note' => $this->notes,
            'items' => $this->items->map(fn (InvoiceItem $i) => ['description' => $i->description, 'quantity' => $i->quantity, 'unit_price_sen' => $i->unit_price_sen])->all(),
            'total_sen' => $this->total_sen,
        ];
    }
}
