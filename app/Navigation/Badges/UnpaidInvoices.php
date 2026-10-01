<?php

namespace App\Navigation\Badges;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;

/** Sidebar badge on "Kewangan": invoices not yet fully paid. */
class UnpaidInvoices
{
    public function __invoke(): int
    {
        return once(fn () => Invoice::query()->where('status', '!=', InvoiceStatus::Paid)->count());
    }
}
