<?php

namespace App\Mail;

use App\Models\Installment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Ansuran reminder: 3 days before the due date (before) or 1 day after (after). */
class InstallmentReminder extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Installment $installment, public string $when) {}

    public function envelope(): Envelope
    {
        $plan = $this->installment->plan;

        return new Envelope(subject: $this->when === 'before'
            ? "Peringatan: Ansuran ke-{$this->installment->seq} {$plan->order_no} perlu dibayar dalam 3 hari"
            : "Ansuran ke-{$this->installment->seq} {$plan->order_no} telah lewat");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.installment-reminder', with: [
            'plan' => $this->installment->plan->loadMissing('customer'),
            'installment' => $this->installment,
            'when' => $this->when,
        ]);
    }
}
