<?php

namespace App\Livewire\Vendors\Tabs;

use Spatie\Activitylog\Models\Activity;

/** Audit Log tab: vendor-scoped activity (vendor, POs, payments, reports, ranking). */
class AuditLog extends VendorTab
{
    public int $limit = 30;

    public function more(): void
    {
        $this->limit += 30;
    }

    /**
     * [icon, tile classes] per event prefix/name.
     *
     * @return array{string, string}
     */
    public static function style(string $event): array
    {
        return match (true) {
            in_array($event, ['po.accepted', 'report.verified', 'payment.completed'], true) => ['seal-check', 'bg-success-soft text-success'],
            $event === 'po.sent' => ['paper-plane-tilt', 'bg-info-soft text-info'],
            str_starts_with($event, 'po.cancel'), $event === 'vendor.suspended', $event === 'vendor.deleted', $event === 'report.revision' => ['x-circle', 'bg-danger-soft text-danger'],
            str_starts_with($event, 'po.') => ['clipboard-text', 'bg-primary-soft text-primary'],
            str_starts_with($event, 'payment.') => ['wallet', 'bg-warning-soft text-warning'],
            str_starts_with($event, 'report.') => ['images', 'bg-info-soft text-info'],
            $event === 'vendor.rank' => ['chart-line-up', 'bg-gold-soft text-gold'],
            default => ['clock-counter-clockwise', 'bg-primary-soft text-primary'],
        };
    }

    public function render(): mixed
    {
        $query = Activity::query()->where('log_name', 'vendors')->where('properties->vendor_id', $this->vendorId);

        return view('livewire.vendors.tabs.audit-log', [
            'total' => (clone $query)->count(),
            'entries' => $query->with('causer')->latest('id')->limit($this->limit)->get(),
        ]);
    }
}
