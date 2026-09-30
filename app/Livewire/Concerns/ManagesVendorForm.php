<?php

namespace App\Livewire\Concerns;

use App\Actions\Vendors\SaveVendor;
use App\Enums\Module;
use App\Livewire\Forms\VendorForm;
use App\Models\User;
use App\Models\Vendor;

/**
 * "Daftar Vendor Baharu" + "Edit Maklumat Vendor" modals (Vendor list and Profil tab).
 * The using component declares `public VendorForm $vendorForm;`.
 */
trait ManagesVendorForm
{
    public bool $showVendorForm = false;

    /** register | edit */
    public string $vendorModal = 'register';

    public function openRegister(): void
    {
        $this->authorize(Module::Vendors->managePermission());
        $this->vendorForm->forNew();
        $this->vendorModal = 'register';
        $this->showVendorForm = true;
    }

    public function openEditVendor(int $vendorId): void
    {
        $this->authorize(Module::Vendors->managePermission());
        $this->vendorForm->fromVendor(Vendor::query()->findOrFail($vendorId));
        $this->vendorModal = 'edit';
        $this->showVendorForm = true;
    }

    public function toggleVendorAnimal(string $animal): void
    {
        $this->vendorForm->toggleAnimal($animal);
    }

    public function saveVendor(SaveVendor $save): void
    {
        $this->authorize(Module::Vendors->managePermission());

        $this->vendorForm->validate();

        /** @var User $actor */
        $actor = auth()->user();
        $vendor = $this->vendorForm->vendorId ? Vendor::query()->findOrFail($this->vendorForm->vendorId) : null;
        $saved = $save->handle($vendor, $this->vendorForm->payload(), $actor);

        $this->showVendorForm = false;
        $this->vendorSaved($saved, $vendor === null);
    }

    /** Hook for the component (refresh caches, toast). */
    abstract protected function vendorSaved(Vendor $vendor, bool $created): void;
}
