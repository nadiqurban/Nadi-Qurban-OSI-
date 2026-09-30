<?php

namespace App\Livewire\Vendors;

use App\Models\User;
use App\Models\Vendor;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Vendor profile (Vendor.dc.html profile view): header, 4 stats and six tabs, each
 * a child component. Vendor PIC users only reach their own vendor and only the
 * PO / Laporan / Bayaran tabs.
 */
#[Layout('layouts::app')]
class Show extends Component
{
    public const TABS = [
        'profil' => ['Profil', 'identification-card'],
        'po' => ['Purchase Order', 'clipboard-text'],
        'bayaran' => ['Bayaran', 'wallet'],
        'laporan' => ['Laporan', 'file-text'],
        'prestasi' => ['Prestasi', 'chart-line-up'],
        'audit' => ['Audit Log', 'clock-counter-clockwise'],
    ];

    public const PIC_TABS = ['po', 'laporan', 'bayaran'];

    public Vendor $vendor;

    #[Url(as: 'tab', except: 'profil')]
    public string $tab = 'profil';

    public function mount(Vendor $vendor): void
    {
        $user = $this->user();

        abort_if($user->isVendorPic() && $user->vendor_id !== $vendor->id, 403);

        $this->vendor = $vendor;

        if (! array_key_exists($this->tab, $this->tabs())) {
            $this->tab = array_key_first($this->tabs()) ?? 'profil';
        }
    }

    /** @return array<string, array{string, string}> */
    public function tabs(): array
    {
        return $this->user()->isVendorPic()
            ? array_intersect_key(self::TABS, array_flip(self::PIC_TABS))
            : self::TABS;
    }

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, $this->tabs())) {
            $this->tab = $tab;
        }
    }

    /** Child tabs dispatch this after changes that affect the header/stats. */
    #[On('vendor-updated')]
    public function refreshVendor(): void
    {
        $this->vendor->refresh();
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }

    public function render(): mixed
    {
        $vendor = Vendor::query()->withPoStats()->with('country')->findOrFail($this->vendor->id);

        return view('livewire.vendors.show', [
            'v' => $vendor,
            'isPic' => $this->user()->isVendorPic(),
        ])->title($vendor->name.' · Vendor');
    }
}
