<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $invoice_id
 * @property int $amount_sen
 * @property string $method
 * @property string|null $reference
 * @property Carbon $paid_at
 * @property int|null $recorded_by
 * @property-read User|null $recorder
 * @property-read Invoice $invoice
 */
class InvoicePayment extends Model
{
    public const METHODS = ['FPX Online', 'Kad Kredit', 'DuitNow QR', 'Pindahan Bank', 'Tunai'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'amount_sen' => 'integer'];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
