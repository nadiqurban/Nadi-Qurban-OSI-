<?php

namespace App\Console\Commands;

use App\Actions\Finance\Invoices;
use App\Enums\InvoiceStatus;
use App\Enums\Module;
use App\Enums\NotificationType;
use App\Models\Invoice;
use App\Support\Notifier;
use Illuminate\Console\Command;

/** Kewangan: re-derive invoice statuses daily (Tertunggak → Lewat after the due date). */
class FinanceDaily extends Command
{
    protected $signature = 'finance:daily';

    protected $description = 'Kemas kini status invois (Lewat selepas tarikh tempoh)';

    public function handle(Invoices $invoices): int
    {
        $lateBefore = Invoice::query()->where('status', InvoiceStatus::Late)->count();
        $this->info($invoices->refreshStatuses().' invois dikemas kini.');

        $late = Invoice::query()->where('status', InvoiceStatus::Late);
        if ((clone $late)->count() > $lateBefore) {
            $sum = (int) (clone $late)->selectRaw('COALESCE(SUM(total_sen - paid_sen), 0) as b')->value('b');
            Notifier::send(NotificationType::PaymentOverdue, Module::Finance->viewPermission(), 'Bayaran tertunggak',
                (clone $late)->count().' invois melebihi tarikh tempoh — jumlah '.rm($sum).'.', route('finance.index', ['status' => 'lewat']));
        }

        return self::SUCCESS;
    }
}
