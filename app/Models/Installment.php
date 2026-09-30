<?php

namespace App\Models;

use App\Enums\InstallmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $installment_plan_id
 * @property int $seq
 * @property Carbon $due_date
 * @property int $amount_sen
 * @property InstallmentStatus $status
 * @property Carbon|null $paid_at
 * @property string|null $method
 * @property string|null $gateway_ref
 * @property int|null $confirmed_by
 * @property Carbon|null $reminded_before_at
 * @property Carbon|null $reminded_after_at
 * @property-read InstallmentPlan $plan
 */
class Installment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => InstallmentStatus::class,
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'reminded_before_at' => 'datetime',
            'reminded_after_at' => 'datetime',
            'seq' => 'integer',
            'amount_sen' => 'integer',
        ];
    }

    /** @return BelongsTo<InstallmentPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(InstallmentPlan::class, 'installment_plan_id');
    }

    public function isPaid(): bool
    {
        return $this->status === InstallmentStatus::Paid;
    }
}
