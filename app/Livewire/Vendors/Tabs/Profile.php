<?php

namespace App\Livewire\Vendors\Tabs;

use App\Enums\PoStatus;
use App\Livewire\Concerns\ManagesVendorForm;
use App\Livewire\Forms\VendorForm;
use App\Models\Vendor;

/** Profil tab: "Maklumat Vendor" (+ Edit Maklumat modal) and "Purchase Order Terkini" with RM/USD. */
class Profile extends VendorTab
{
    use ManagesVendorForm;

    public VendorForm $vendorForm;

    protected function vendorSaved(Vendor $vendor, bool $created): void
    {
        $this->dispatch('vendor-updated');
        $this->dispatch('toast', message: 'Maklumat vendor disimpan.');
    }

    public function render(): mixed
    {
        $vendor = $this->vendor();

        return view('livewire.vendors.tabs.profile', [
            'v' => $vendor,
            'orders' => $vendor->purchaseOrders()->where('status', '!=', PoStatus::Cancelled)->limit(6)->get(),
            'canManage' => $this->canManage(),
            'usdRate' => $this->usdRate(),
        ]);
    }
}
