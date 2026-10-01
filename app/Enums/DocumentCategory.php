<?php

namespace App\Enums;

/** Dokumen category rail (Dokumen.dc.html catDefs; "Semua Fail" is the unfiltered view). */
enum DocumentCategory: string
{
    case Certificate = 'sijil';
    case Report = 'laporan';
    case Invoice = 'invois';
    case Media = 'media';
    case Agreement = 'perjanjian';
    case PaymentReceipt = 'resit';

    public function label(): string
    {
        return match ($this) {
            self::Certificate => 'Sijil Qurban',
            self::Report => 'Laporan Pelaksanaan',
            self::Invoice => 'Invois & Resit',
            self::Media => 'Video & Foto',
            self::Agreement => 'Perjanjian Vendor',
            self::PaymentReceipt => 'Upload Payment Receipt',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Certificate => 'certificate',
            self::Report => 'file-text',
            self::Invoice => 'receipt',
            self::Media => 'video',
            self::Agreement => 'scroll',
            self::PaymentReceipt => 'bank',
        };
    }
}
