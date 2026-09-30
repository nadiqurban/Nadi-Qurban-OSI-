<?php

namespace App\Enums;

/**
 * Fixed roles (PRD §3). "Operasi" is added to the design's matrix (PRD §12.20);
 * the design's "Vendor" column is the Vendor PIC role.
 */
enum RoleName: string
{
    case SuperAdmin = 'Super Admin';
    case AdminHq = 'Admin HQ';
    case Finance = 'Kewangan';
    case Sales = 'Sales';
    case Operations = 'Operasi';
    case VendorPic = 'Vendor PIC';

    public function description(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Akses penuh semua modul & tetapan sistem.',
            self::AdminHq => 'Urus operasi, tempahan, vendor & verifikasi.',
            self::Finance => 'Akses invois, bayaran & laporan kewangan.',
            self::Sales => 'Urus lead, CRM & tempahan pelanggan.',
            self::Operations => 'Urus agihan, pelaksanaan & penghantaran AWB.',
            self::VendorPic => 'Akses PO & muat naik laporan pelaksanaan.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::SuperAdmin => 'crown',
            self::AdminHq => 'shield-star',
            self::Finance => 'wallet',
            self::Sales => 'handshake',
            self::Operations => 'gear-six',
            self::VendorPic => 'truck',
        };
    }

    /** Card icon tone (App\Support\Tone). */
    public function tone(): string
    {
        return match ($this) {
            self::SuperAdmin => 'danger',
            self::AdminHq => 'primary',
            self::Finance => 'success',
            self::Sales => 'info',
            self::Operations => 'purple',
            self::VendorPic => 'gold',
        };
    }

    /** Role tag in the users table (roleStyleMap: Vendor uses gold-soft + gold-ink). */
    public function tagTone(): string
    {
        return $this === self::VendorPic ? 'gold-ink' : $this->tone();
    }

    /**
     * Default permission matrix column, in Module::cases() order:
     * Dashboard, Ansuran, Tempahan, Pengesahan, Akad, Agihan, Pelaksanaan, AWB,
     * Selesai, Vendor, Produk, Dokumen, CRM, Promo, Kewangan, Laporan, Audit,
     * Notifikasi, Pengguna, Sijil, Tetapan, Webhooks, API.
     * Columns 1–5 = design `mrows`; Operasi is new (PRD §12.20).
     *
     * @return array<string, AccessLevel>
     */
    public function defaultMatrix(): array
    {
        $column = match ($this) {
            self::SuperAdmin => 'FFFFFFFFFFFFFFFFFFFFFFF',
            self::AdminHq => 'FFFFFFFFFFFFVFVFVFVFVNN',
            self::Finance => 'VFVFNNNNVNNVNNFFVVNVNNN',
            self::Sales => 'VVFNNNNNVNFVFFNVNVNVNNN',
            self::Operations => 'VNVNFFFFVVVVNNNNNVNVNNN',
            self::VendorPic => 'NNNNVVFVVVNVNNNNNVNNNNN',
        };

        $codes = str_split($column);

        return collect(Module::cases())
            ->mapWithKeys(fn (Module $m, int $i) => [$m->value => AccessLevel::from($codes[$i])])
            ->all();
    }
}
