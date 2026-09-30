<?php

namespace Database\Seeders;

use App\Actions\Vendors\PurchaseOrders;
use App\Enums\PoStatus;
use App\Enums\VendorPaymentStatus;
use App\Enums\VendorReportStatus;
use App\Enums\VendorStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Local/demo only: Purchase Orders from Vendor.dc.html (poData) for Uganda Charity
 * plus a few for the other vendors, with payments and one verified report.
 */
class DemoPurchaseOrderSeeder extends Seeder
{
    public function run(PurchaseOrders $orders): void
    {
        if (PurchaseOrder::query()->exists()) {
            return;
        }

        $admin = User::query()->where('email', 'nurfitri@nadiqurban.com')->first();
        $pic = User::query()->where('email', 'vendor@albarakah.ug')->first() ?? $admin;

        if (! $admin) {
            return;
        }

        // [vendor code, service, animal, qty, unit RM, date, status, notes, payment [status, date, bank, ref]]
        $rows = [
            ['SP 001', 'qurban', 'Lembu (1 bhg)', 25, 1800, '2027-06-18', PoStatus::InProgress, 'Sila pastikan sembelihan mengikut syariat & rekod video setiap ekor.', [VendorPaymentStatus::Pending, null, 'Bank Islam', null]],
            ['SP 001', 'qurban', 'Kambing', 40, 800, '2027-06-18', PoStatus::Completed, 'Agihan daging kepada asnaf tempatan selepas sembelihan.', [VendorPaymentStatus::Completed, '2027-06-11', 'Maybank', 'MBB27061100482']],
            ['SP 001', 'aqiqah', 'Kambing', 18, 800, '2027-06-20', PoStatus::Accepted, 'Aqiqah bagi 9 bayi — 2 ekor setiap seorang.', [VendorPaymentStatus::Processing, '2027-06-12', 'CIMB Bank', 'CIMB27061200913']],
            ['SP 001', 'qurban', 'Lembu (7 bhg)', 30, 1800, '2027-06-19', PoStatus::Sent, 'PO dihantar, menunggu pengesahan vendor.', null],
            ['SP 001', 'nazar', 'Kambing', 12, 850, '2027-06-22', PoStatus::Draft, 'Draf PO — belum dihantar kepada vendor.', null],
            ['SP 001', 'qurban', 'Lembu (1 bhg)', 22, 1800, '2027-06-17', PoStatus::Cancelled, 'Dibatalkan atas permintaan HQ — kuota mencukupi.', null],
            ['SP 002', 'aqiqah', 'Kambing', 60, 850, '2027-06-18', PoStatus::InProgress, 'Aqiqah tempatan — agihan di Selangor.', [VendorPaymentStatus::Completed, '2027-06-10', 'CIMB Bank', 'CIMB27061000211']],
            ['SP 003', 'qurban', 'Lembu (7 bhg)', 35, 1500, '2027-06-18', PoStatus::Sent, 'Sembelihan di Abéché.', null],
            ['SP 004', 'qurban', 'Kambing', 20, 700, '2027-06-18', PoStatus::Draft, 'Menunggu kelulusan vendor baharu.', null],
            ['SP 005', 'qurban', 'Kambing', 45, 650, '2027-06-18', PoStatus::Completed, 'Qurban kambing Ahmedabad.', [VendorPaymentStatus::Completed, '2027-06-09', 'Maybank', 'MBB27060900155']],
            ['SP 006', 'dam', 'Unta', 6, 5200, '2026-05-30', PoStatus::Completed, 'Dam haji musim lalu.', [VendorPaymentStatus::Completed, '2026-05-20', 'Maybank', 'MBB26052000901']],
        ];

        foreach ($rows as $n => [$code, $service, $animal, $qty, $unit, $date, $status, $notes, $payment]) {
            $vendor = Vendor::query()->where('code', $code)->firstOrFail();
            $wasSuspended = $vendor->status;
            $vendor->status = VendorStatus::Active;   // allow creation for history

            $po = $orders->create($vendor, [
                'service' => $service, 'animal_label' => $animal, 'quantity' => $qty, 'unit_price' => $unit,
                'currency' => 'RM', 'implementation_date' => $date, 'notes' => $notes,
            ], $admin);
            $vendor->status = $wasSuspended;

            $issued = Carbon::parse('2027-06-08 09:00')->subDays(max(0, 5 - $n));
            $step = $status->step();
            $po->forceFill([
                'status' => $status,
                'created_at' => $issued,
                'sent_at' => $step >= 1 && $status !== PoStatus::Cancelled ? $issued->copy()->addDay() : null,
                'accepted_at' => $step >= 2 ? $issued->copy()->addDays(3)->setTime(14, 22) : null,
                'accepted_by' => $step >= 2 ? $pic?->id : null,
                'in_progress_at' => $step >= 3 ? $issued->copy()->addDays(5) : null,
                'completed_at' => $step >= 4 ? $issued->copy()->addDays(7) : null,
                'cancelled_at' => $status === PoStatus::Cancelled ? $issued->copy()->addDays(2) : null,
            ])->save();

            if ($payment) {
                [$payStatus, $payDate, $bank, $ref] = $payment;
                $po->payment?->forceFill([
                    'status' => $payStatus, 'payment_date' => $payDate, 'bank' => $bank, 'reference' => $ref,
                    'approved_by_name' => 'Fatimah Noor',
                    'confirmed_by' => $payStatus === VendorPaymentStatus::Completed ? $admin->id : null,
                    'confirmed_at' => $payStatus === VendorPaymentStatus::Completed ? Carbon::parse((string) $payDate)->setTime(11, 5) : null,
                ])->save();

                if ($payStatus !== VendorPaymentStatus::Pending) {
                    $po->payment?->addMediaFromString($this->png('RESIT BANK '.$ref, [22, 163, 74]))->usingFileName('resit-bayaran-'.$po->po_no.'.png')->toMediaCollection('receipt');
                }
            }

            if (in_array($status, [PoStatus::InProgress, PoStatus::Completed], true)) {
                $isDone = $status === PoStatus::Completed;
                $report = VendorReport::query()->create([
                    'purchase_order_id' => $po->id,
                    'vendor_id' => $vendor->id,
                    'notes' => "Sembelihan {$qty} ekor ".mb_strtolower($animal).' disempurnakan mengikut syariat. Daging diagihkan kepada keluarga asnaf. Video & gambar setiap ekor disertakan.',
                    'status' => $isDone ? VendorReportStatus::Verified : VendorReportStatus::Submitted,
                    'submitted_by' => $pic?->id,
                    'submitted_at' => $po->in_progress_at,
                    'verified_by' => $isDone ? $admin->id : null,
                    'verified_at' => $po->completed_at,
                ]);

                foreach ([['Sembelihan', [120, 132, 70]], ['Agihan', [158, 128, 62]]] as [$label, $rgb]) {
                    $report->addMediaFromString($this->png(mb_strtoupper("{$label} {$animal}"), $rgb))
                        ->usingFileName("Foto-{$label}-".str_replace([' ', '(', ')'], '', $animal).'.png')
                        ->withCustomProperties(['animal' => str_starts_with($animal, 'Lembu') ? 'Lembu' : (str_starts_with($animal, 'Unta') ? 'Unta' : 'Kambing')])
                        ->toMediaCollection('files');
                }
            }
        }
    }

    /** @param  array{int, int, int}  $rgb */
    private function png(string $caption, array $rgb): string
    {
        $img = imagecreatetruecolor(800, 600);
        imagefilledrectangle($img, 0, 0, 800, 600, (int) imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]));
        imagefilledrectangle($img, 0, 480, 800, 600, (int) imagecolorallocate($img, 66, 72, 28));
        imagestring($img, 5, 30, 520, $caption, (int) imagecolorallocate($img, 255, 255, 255));
        ob_start();
        imagepng($img);

        return (string) ob_get_clean();
    }
}
