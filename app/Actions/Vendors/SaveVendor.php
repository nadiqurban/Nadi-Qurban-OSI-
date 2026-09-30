<?php

namespace App\Actions\Vendors;

use App\Enums\Severity;
use App\Enums\VendorLevel;
use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Audit;
use App\Support\Sequence;
use Illuminate\Support\Facades\DB;

/** "Daftar Vendor" and "Edit Maklumat Vendor". */
class SaveVendor
{
    /**
     * @param  array<string, mixed>  $data  validated fields (code, vendor_no, name, company, supplier, country_id,
     *                                      level, status, phone, email, pic_name, animals, bank_name, bank_holder,
     *                                      bank_account, swift, bank_address)
     */
    public function handle(?Vendor $vendor, array $data, User $actor): Vendor
    {
        return DB::transaction(function () use ($vendor, $data, $actor) {
            $isNew = $vendor === null;
            $vendor ??= new Vendor([
                'status' => VendorStatus::Active,
                'rank' => 5,
                'level' => VendorLevel::Silver,
                'rating' => 0,
            ]);

            $vendor->fill($data);

            if ($isNew && empty($vendor->vendor_no)) {
                $vendor->vendor_no = 'VND-'.Sequence::next('vendor', 2010);
            }

            $vendor->save();

            Audit::log($isNew ? 'vendor.created' : 'vendor.updated', ($isNew ? 'Vendor didaftarkan: ' : 'Maklumat vendor dikemaskini: ').$vendor->name,
                $vendor, Severity::Info, ['vendor_id' => $vendor->id, 'changes' => $isNew ? [] : array_keys($vendor->getChanges())], $actor, 'vendors');

            return $vendor;
        });
    }

    public function suspend(Vendor $vendor, User $actor): void
    {
        $vendor->forceFill(['status' => VendorStatus::Suspended])->save();
        Audit::log('vendor.suspended', "Vendor {$vendor->name} dinyahaktifkan", $vendor, Severity::Warning, ['vendor_id' => $vendor->id], $actor, 'vendors');
    }

    public function delete(Vendor $vendor, User $actor): void
    {
        Audit::log('vendor.deleted', "Vendor {$vendor->name} dibuang", $vendor, Severity::Warning, ['vendor_id' => $vendor->id], $actor, 'vendors');
        $vendor->delete();
    }
}
