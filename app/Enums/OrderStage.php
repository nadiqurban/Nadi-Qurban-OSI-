<?php

namespace App\Enums;

/**
 * Order pipeline (PRD §5). Stages only move forward except an explicit cancel.
 * HQ sees 12 steps ("Aliran Kerja"); customers see 7 (Jejak Status).
 */
enum OrderStage: string
{
    case Received = 'received';
    case PaymentVerified = 'payment_verified';
    case AkadDone = 'akad_done';
    case CountryAssigned = 'country_assigned';
    case VendorAssigned = 'vendor_assigned';
    case Executing = 'executing';
    case ReportUploaded = 'report_uploaded';
    case ReportVerified = 'report_verified';
    case FinalReport = 'final_report';
    case AwbGenerated = 'awb_generated';
    case CertificatePosted = 'certificate_posted';
    case Completed = 'completed';

    public function position(): int
    {
        return (int) array_search($this, self::cases(), true);
    }

    /** HQ label (12-step "Aliran Kerja" in the order detail). */
    public function label(): string
    {
        return match ($this) {
            self::Received => 'Tempahan Diterima',
            self::PaymentVerified => 'Pengesahan Bayaran',
            self::AkadDone => 'Akad',
            self::CountryAssigned => 'Assign Negara',
            self::VendorAssigned => 'Assign Vendor',
            self::Executing => 'Pelaksanaan',
            self::ReportUploaded => 'Upload Laporan',
            self::ReportVerified => 'HQ Verify',
            self::FinalReport => 'Generate Final Report',
            self::AwbGenerated => 'Generate AWB',
            self::CertificatePosted => 'Sijil Dipos',
            self::Completed => 'Completed',
        };
    }

    /** Customer label (7-step Jejak Status). */
    public function customerLabel(): string
    {
        return match ($this) {
            self::Received => 'Tempahan Diterima',
            self::PaymentVerified => 'Bayaran Disahkan',
            self::AkadDone => 'Lafaz Akad',
            self::CountryAssigned, self::VendorAssigned, self::Executing, self::ReportUploaded => 'Agihan Negara',
            self::ReportVerified, self::FinalReport => 'Ibadah Dilaksanakan',
            self::AwbGenerated, self::CertificatePosted => 'Sijil Dihantar',
            self::Completed => 'Selesai',
        };
    }

    public function isAfter(self $other): bool
    {
        return $this->position() > $other->position();
    }
}
