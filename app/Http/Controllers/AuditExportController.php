<?php

namespace App\Http\Controllers;

use App\Enums\Severity;
use App\Livewire\Audit\Index;
use App\Models\User;
use App\Support\Audit;
use App\Support\AuditAction;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Audit Log → Eksport CSV with the screen's filters (route requires audit.view). */
class AuditExportController
{
    public function __invoke(Request $request): StreamedResponse
    {
        $range = (string) $request->query('tempoh', '7h');
        $query = Index::filtered($range, (string) $request->query('tahap', ''), (string) $request->query('q', ''))
            ->with('causer')->latest()->latest('id');

        /** @var User $actor */
        $actor = $request->user();
        Audit::log('audit.export', 'Eksport log audit ('.(Index::RANGES[$range] ?? $range).')', causer: $actor, logName: 'audit');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($out, ['Log ID', 'Masa', 'Pengguna', 'Peranan', 'Tindakan', 'Butiran', 'Tahap', 'Alamat IP', 'Event']);

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $a) {
                    /** @var Activity $a */
                    $causer = $a->causer instanceof User ? $a->causer : null;
                    fputcsv($out, [
                        'LOG-'.$a->id,
                        $a->created_at?->format('Y-m-d H:i:s'),
                        $causer->name ?? 'Sistem',
                        $causer->role_label ?? 'Automasi',
                        AuditAction::label($a->event),
                        $a->description,
                        (Severity::tryFrom((string) $a->getAttribute('severity')) ?? Severity::Info)->label(),
                        $a->getAttribute('ip_address'),
                        $a->event,
                    ]);
                }
            });

            fclose($out);
        }, 'Audit-Log-Nadi-Qurban-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
