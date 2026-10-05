<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Kewangan demo invoices (Kewangan.dc.html `raw`), dated relative to today so statuses stay realistic. */
class DemoFinanceSeeder extends Seeder
{
    public function run(): void
    {
        $settings = app(Settings::class);
        $company = [
            'name' => (string) $settings->get('company.name', Settings::COMPANY_DEFAULTS['company.name']),
            'ssm' => (string) $settings->get('company.ssm', Settings::COMPANY_DEFAULTS['company.ssm']),
            'address' => (string) $settings->get('company.address', Settings::COMPANY_DEFAULTS['company.address']),
            'phone' => (string) $settings->get('company.phone', Settings::COMPANY_DEFAULTS['company.phone']),
            'email' => 'finance@nadiqurban.com',
        ];
        $year = (int) $settings->get('season.year', now()->year);

        // [seq, name, order, total RM, issued (days ago), status, term days]
        $rows = [
            [891, 'Ahmad Zaki bin Hassan', 'NQ-2027-001248', 2450, 2, InvoiceStatus::Deposit, 14],
            [890, 'Nurul Aina binti Rahim', 'NQ-2027-001247', 850, 2, InvoiceStatus::Paid, 0],
            [889, 'Mohd Firdaus bin Omar', 'NQ-2027-001246', 5200, 3, InvoiceStatus::Outstanding, 14],
            [888, 'Siti Khadijah bt Yusof', 'NQ-2027-001245', 720, 3, InvoiceStatus::Paid, 0],
            [887, 'Zulhilmi bin Abdullah', 'NQ-2027-001244', 1960, 4, InvoiceStatus::Outstanding, 14],
            [886, 'Rosmah binti Idris', 'NQ-2027-001243', 2300, 22, InvoiceStatus::Late, 10],
            [885, 'Aminah binti Salleh', 'NQ-2027-001241', 2150, 6, InvoiceStatus::Paid, 0],
            [884, 'Ibrahim bin Musa', 'NQ-2027-001240', 980, 6, InvoiceStatus::Paid, 0],
            [883, 'Hafiz bin Kamarudin', 'NQ-2027-001238', 3500, 48, InvoiceStatus::Late, 14],
            [882, 'Faridah binti Hamzah', 'NQ-2027-001236', 1800, 40, InvoiceStatus::Paid, 0],
            [881, 'Kamal bin Ariffin', 'NQ-2027-001233', 2450, 65, InvoiceStatus::Paid, 0],
            [880, 'Norhayati binti Said', 'NQ-2027-001230', 7000, 80, InvoiceStatus::Paid, 0],
        ];
        if (SampleDataSeeder::$active) {
            $rows = array_slice($rows, 0, 1);   // deposit paid, balance outstanding
        }

        $methods = InvoicePayment::METHODS;

        DB::transaction(function () use ($rows, $company, $year, $methods) {
            foreach ($rows as $i => [$seq, $name, $order, $rm, $ago, $status, $term]) {
                $total = $rm * 100;
                $issued = today()->subDays($ago);
                $paid = match ($status) {
                    InvoiceStatus::Paid => $total,
                    InvoiceStatus::Deposit => intdiv($total, 2),
                    default => 0,
                };

                $invoice = Invoice::query()->updateOrCreate(['invoice_no' => sprintf('INV-%d-%04d', $year, $seq)], [
                    'order_no' => $order,
                    'customer_name' => $name,
                    'customer_phone' => '01'.(2 + $i % 7).'-'.(3000000 + $seq * 917),
                    'customer_address' => 'No. 12, Jln Melati 3, 40150 Shah Alam',
                    'company' => $company,
                    'subtotal_sen' => $total,
                    'tax_sen' => 0,
                    'total_sen' => $total,
                    'paid_sen' => $paid,
                    'issue_date' => $issued,
                    'due_date' => $issued->copy()->addDays($term),
                    'status' => $status,
                    'notes' => 'Sila jelaskan bayaran sebelum tarikh tempoh. Terima kasih.',
                    'created_at' => $issued->copy()->setTime(9, 14),
                ]);

                $invoice->items()->delete();
                $invoice->payments()->delete();
                $lines = [
                    ['Qurban Lembu (1 bahagian)', 'Pelaksanaan: Uganda', (int) round($total * 0.735 / 5000) * 5000],
                    ['Yuran Pengurusan & Logistik', 'Termasuk pengangkutan', (int) round($total * 0.184 / 5000) * 5000],
                ];
                $lines[] = ['Sijil & Penghantaran', 'Sijil fizikal + video', $total - $lines[0][2] - $lines[1][2]];
                foreach ($lines as $pos => [$desc, $detail, $amount]) {
                    $invoice->items()->create(['description' => $desc, 'detail' => $detail, 'quantity' => 1, 'unit_price_sen' => $amount, 'line_total_sen' => $amount, 'position' => $pos]);
                }

                if ($paid > 0) {
                    $invoice->payments()->create([
                        'amount_sen' => $paid,
                        'method' => $methods[$i % 4],
                        'reference' => 'FPX'.$year.sprintf('%06d', $seq),
                        'paid_at' => $issued->copy()->setTime(14, 20),
                    ]);
                }
            }

            DB::table('sequences')->updateOrInsert(['name' => "invoice-{$year}"], ['next_value' => 892, 'updated_at' => now(), 'created_at' => now()]);
        });
    }
}
