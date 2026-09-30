<?php

namespace App\Actions\Vendors;

use App\Enums\RoleName;
use App\Enums\Severity;
use App\Enums\VendorPaymentStatus;
use App\Models\User;
use App\Models\VendorPayment;
use App\Support\Audit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Bayaran tab (HQ only): payment info + receipt → Payment Processing;
 * "Sah Bayaran" (Super Admin / Admin HQ only) → Payment Completed.
 */
class VendorPayments
{
    public static function canConfirm(User $user): bool
    {
        return $user->hasAnyRole([RoleName::SuperAdmin->value, RoleName::AdminHq->value]);
    }

    /**
     * @param  array{payment_date: ?string, approved_by_name: ?string, bank: ?string, reference: ?string}  $data
     */
    public function saveInfo(VendorPayment $payment, array $data, ?UploadedFile $receipt, ?UploadedFile $advice, User $actor): void
    {
        if ($payment->status === VendorPaymentStatus::Completed) {
            throw ValidationException::withMessages(['payment' => 'Bayaran telah disahkan. Klik Edit untuk mengubah.']);
        }

        DB::transaction(function () use ($payment, $data, $receipt, $advice, $actor) {
            $payment->fill($data);
            $filled = $payment->payment_date && $payment->bank && $payment->reference;

            if ($filled && $payment->status === VendorPaymentStatus::Pending) {
                $payment->status = VendorPaymentStatus::Processing;
            }

            $payment->save();

            if ($receipt) {
                $payment->addMedia($receipt)->usingFileName('resit-bayaran-'.$payment->purchaseOrder->po_no.'.'.$receipt->extension())->toMediaCollection('receipt');
            }

            if ($advice) {
                $payment->addMedia($advice)->usingFileName('payment-advice-'.$payment->purchaseOrder->po_no.'.pdf')->toMediaCollection('advice');
            }

            $this->log($payment, 'payment.updated', "Maklumat bayaran {$payment->purchaseOrder->po_no} disimpan", $actor);
        });
    }

    public function removeFile(VendorPayment $payment, string $collection, User $actor): void
    {
        if ($payment->status === VendorPaymentStatus::Completed || ! in_array($collection, ['receipt', 'advice'], true)) {
            throw ValidationException::withMessages(['payment' => 'Resit tidak boleh dibuang selepas bayaran disahkan.']);
        }

        $payment->clearMediaCollection($collection);
        $this->log($payment, 'payment.receipt_removed', "Resit bayaran {$payment->purchaseOrder->po_no} dibuang", $actor, Severity::Warning);
    }

    public function confirm(VendorPayment $payment, User $actor): void
    {
        if (! self::canConfirm($actor)) {
            abort(403, 'Hanya Superadmin & Admin HQ boleh sahkan bayaran.');
        }

        if (! $payment->payment_date || ! $payment->bank || ! $payment->reference) {
            throw ValidationException::withMessages(['payment' => 'Lengkapkan tarikh, bank dan no. rujukan sebelum sahkan bayaran.']);
        }

        $payment->forceFill(['status' => VendorPaymentStatus::Completed, 'confirmed_by' => $actor->id, 'confirmed_at' => now()])->save();
        $this->log($payment, 'payment.completed', 'Bayaran '.rm($payment->amount_sen)." {$payment->purchaseOrder->po_no} disahkan — Payment Completed", $actor);
    }

    /** "Edit" after confirmation: back to Payment Processing. */
    public function reopen(VendorPayment $payment, User $actor): void
    {
        if (! self::canConfirm($actor)) {
            abort(403);
        }

        $payment->forceFill(['status' => VendorPaymentStatus::Processing, 'confirmed_by' => null, 'confirmed_at' => null])->save();
        $this->log($payment, 'payment.reopened', "Pengesahan bayaran {$payment->purchaseOrder->po_no} dibuka semula", $actor, Severity::Warning);
    }

    private function log(VendorPayment $payment, string $event, string $description, User $actor, Severity $severity = Severity::Info): void
    {
        Audit::log($event, $description, $payment, $severity, ['vendor_id' => $payment->vendor_id, 'ref' => $payment->purchaseOrder->po_no], $actor, 'vendors');
    }
}
