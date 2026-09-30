<?php

namespace App\Livewire\Vendors\Tabs;

use App\Enums\Module;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Settings;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Shared base for the vendor profile tabs: the (locked) vendor id, the Vendor PIC
 * ownership guard and the RM/USD display toggle.
 */
abstract class VendorTab extends Component
{
    #[Locked]
    public int $vendorId;

    /** Display currency toggle (RM / USD). */
    public string $display = 'RM';

    public function mount(int $vendorId): void
    {
        $this->vendorId = $vendorId;
        $this->guardVendor();
    }

    public function hydrate(): void
    {
        $this->guardVendor();
    }

    private function guardVendor(): void
    {
        $user = $this->user();

        abort_unless($user->can(Module::Vendors->viewPermission()), 403);
        abort_if($user->isVendorPic() && $user->vendor_id !== $this->vendorId, 403);
    }

    public function setDisplay(string $currency): void
    {
        $this->display = $currency === 'USD' ? 'USD' : 'RM';
    }

    protected function vendor(): Vendor
    {
        /** @var Vendor */
        return Vendor::query()->with('country')->findOrFail($this->vendorId);
    }

    protected function user(): User
    {
        /** @var User */
        return auth()->user();
    }

    /** HQ with vendors.manage (not a vendor PIC). */
    protected function canManage(): bool
    {
        $user = $this->user();

        return ! $user->isVendorPic() && $user->can(Module::Vendors->managePermission());
    }

    protected function usdRate(): float
    {
        return (float) app(Settings::class)->get('finance.usd_rate', '4.70');
    }
}
