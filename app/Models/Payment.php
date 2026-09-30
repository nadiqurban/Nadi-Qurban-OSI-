<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Payment for an order; the proof (receipt image / PDF) lives on the private disk.
 *
 * @property int $id
 * @property int $order_id
 * @property PaymentMethod $method
 * @property string|null $channel
 * @property string|null $gateway_status
 * @property int $amount_sen
 * @property Carbon|null $paid_at
 * @property string|null $reference
 * @property PaymentStatus $status
 * @property int|null $verified_by
 * @property Carbon|null $verified_at
 * @property string|null $rejection_reason
 * @property-read Order $order
 */
class Payment extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const PROOF_MIMES = ['image/png', 'image/jpeg', 'application/pdf'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount_sen' => 'integer',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('proof')
            ->singleFile()
            ->useDisk('local')
            ->acceptsMimeTypes(self::PROOF_MIMES);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function proof(): ?Media
    {
        return $this->getFirstMedia('proof');
    }

    /** Short-lived signed URL to stream the private proof file. */
    public function proofUrl(int $minutes = 30): ?string
    {
        $media = $this->proof();

        return $media ? URL::temporarySignedRoute('payments.proof', now()->addMinutes($minutes), ['payment' => $this->id]) : null;
    }

    public function proofIsPdf(): bool
    {
        return $this->proof()?->mime_type === 'application/pdf';
    }

    /**
     * List pill: "FPX · Berjaya" / "FPX · Gagal" / "Cek" / "Pindahan Bank" (+ tone).
     *
     * @return array{label: string, tone: string}
     */
    public function pill(): array
    {
        if ($this->order->is_instalment) {
            return ['label' => 'Ansuran', 'tone' => 'purple'];
        }

        if ($this->method === PaymentMethod::Fpx) {
            return match ($this->gateway_status) {
                'berjaya' => ['label' => 'FPX · Berjaya', 'tone' => 'success'],
                'gagal' => ['label' => 'FPX · Gagal', 'tone' => 'danger'],
                default => ['label' => 'FPX', 'tone' => 'primary'],
            };
        }

        return ['label' => $this->method->label(), 'tone' => 'primary'];
    }
}
