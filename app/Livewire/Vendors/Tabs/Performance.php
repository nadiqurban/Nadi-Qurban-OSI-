<?php

namespace App\Livewire\Vendors\Tabs;

use App\Actions\Vendors\SetVendorRank;
use App\Enums\PoStatus;
use App\Enums\VendorLevel;
use App\Enums\VendorReportStatus;
use App\Models\PurchaseOrder;

/**
 * Prestasi tab: 1–10 ranking (Super Admin only — replaces the prototype's
 * "Tukar ke Admin Sistem" demo toggle), tier from rank, metrics computed from
 * POs/reports, and the ranking history.
 */
class Performance extends VendorTab
{
    public function setRank(int $rank, SetVendorRank $action): void
    {
        abort_unless(SetVendorRank::canChange($this->user()), 403);

        $action->handle($this->vendor(), $rank, $this->user());
        $this->dispatch('vendor-updated');
        $this->dispatch('toast', message: "Ranking dikemaskini kepada {$rank}.");
    }

    /** @return list<array{icon: string, color: string, bar: string, label: string, value: string, pct: int}> */
    private function metrics(): array
    {
        $vendor = $this->vendor();
        $orders = PurchaseOrder::query()->where('vendor_id', $vendor->id)->with('report')->get();
        $completed = $orders->where('status', PoStatus::Completed);
        $onTime = $completed->filter(fn (PurchaseOrder $po) => $po->completed_at && (! $po->implementation_date || $po->completed_at->lte($po->implementation_date->copy()->endOfDay()->addDays(7))));
        $issued = $orders->filter(fn (PurchaseOrder $po) => in_array($po->status, [PoStatus::Accepted, PoStatus::InProgress, PoStatus::Completed], true));
        $reported = $issued->filter(fn (PurchaseOrder $po) => in_array($po->report?->status, [VendorReportStatus::Submitted, VendorReportStatus::Verified], true));
        $rating = (float) $vendor->rating;

        $pct = fn (int $part, int $whole) => $whole > 0 ? (int) round($part / $whole * 100) : 0;
        $onTimePct = $pct($onTime->count(), $completed->count());
        $reportPct = $pct($reported->count(), $issued->count());

        return [
            ['icon' => 'clock', 'color' => 'text-primary', 'bar' => 'bg-primary', 'label' => 'On Time Performance', 'value' => $completed->isEmpty() ? '—' : $onTimePct.'%', 'pct' => $onTimePct],
            ['icon' => 'star', 'color' => 'text-gold', 'bar' => 'bg-gold', 'label' => 'Quality Rating', 'value' => number_format($rating * 2, 1).' / 10', 'pct' => (int) round($rating * 20)],
            ['icon' => 'file-text', 'color' => 'text-info', 'bar' => 'bg-info', 'label' => 'Report Submission', 'value' => $issued->isEmpty() ? '—' : $reportPct.'%', 'pct' => $reportPct],
            ['icon' => 'medal', 'color' => 'text-success', 'bar' => 'bg-success', 'label' => 'Vendor Rating (purata)', 'value' => number_format($rating, 1).' / 5', 'pct' => (int) round($rating * 20)],
        ];
    }

    public function render(): mixed
    {
        $vendor = $this->vendor();
        $rank = (int) ($vendor->rank ?? 5);

        return view('livewire.vendors.tabs.performance', [
            'rank' => $rank,
            'tier' => VendorLevel::fromRank($rank),
            'canChange' => SetVendorRank::canChange($this->user()),
            'metrics' => $this->metrics(),
            'history' => $vendor->rankHistories()->with('changedBy')->limit(20)->get(),
        ]);
    }
}
