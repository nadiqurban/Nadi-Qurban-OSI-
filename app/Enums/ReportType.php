<?php

namespace App\Enums;

/** Pusat Laporan templates (Pusat Laporan.dc.html `templates`). */
enum ReportType: string
{
    case Sales = 'jualan';
    case Country = 'negara';
    case Vendor = 'vendor';
    case Finance = 'kewangan';
    case Certificates = 'sijil';
    case Participants = 'peserta';

    public function label(): string
    {
        return match ($this) {
            self::Sales => 'Ringkasan Jualan',
            self::Country => 'Prestasi Negara',
            self::Vendor => 'Prestasi Vendor',
            self::Finance => 'Kewangan & Aliran Tunai',
            self::Certificates => 'Status Pensijilan',
            self::Participants => 'Peserta & Ibadah',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Sales => 'Nilai tempahan, tren bulanan & pencapaian sasaran.',
            self::Country => 'Agihan tempahan & pelaksanaan ikut negara.',
            self::Vendor => 'Kadar penyiapan & rating vendor.',
            self::Finance => 'Kutipan, bayaran vendor & tertunggak.',
            self::Certificates => 'Sijil dijana, dipos & tertunggak.',
            self::Participants => 'Bilangan peserta ikut jenis ibadah.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Sales => 'chart-line-up',
            self::Country => 'globe-hemisphere-west',
            self::Vendor => 'truck',
            self::Finance => 'wallet',
            self::Certificates => 'certificate',
            self::Participants => 'users-three',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Sales => 'primary',
            self::Country => 'info',
            self::Vendor => 'gold',
            self::Finance => 'success',
            self::Certificates => 'purple',
            self::Participants => 'danger',
        };
    }

    /** Default format on the quick-generate card. */
    public function defaultFormat(): string
    {
        return match ($this) {
            self::Country, self::Finance => 'xlsx',
            self::Participants => 'csv',
            default => 'pdf',
        };
    }

    public function frequency(): string
    {
        return match ($this) {
            self::Sales, self::Vendor, self::Finance => 'Bulanan',
            self::Certificates => 'Mingguan',
            default => 'Musim',
        };
    }
}
