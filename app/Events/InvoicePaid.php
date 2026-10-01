<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Fired when an invoice becomes fully paid (notifications listen here). */
class InvoicePaid
{
    use Dispatchable, SerializesModels;

    public function __construct(public Invoice $invoice) {}
}
