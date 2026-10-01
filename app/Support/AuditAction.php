<?php

namespace App\Support;

/**
 * Audit Log action types (Audit Log.dc.html actMap) derived from the event
 * name written by Audit::log(), plus a short "Tindakan" label.
 */
final class AuditAction
{
    /** type => [icon, tone, summary label] */
    public const TYPES = [
        'login' => ['sign-in', 'info', 'Log Masuk / Keluar'],
        'create' => ['plus-circle', 'success', 'Cipta Rekod'],
        'edit' => ['pencil-simple', 'gold', 'Kemaskini'],
        'delete' => ['trash', 'danger', 'Padam / Batal'],
        'verify' => ['shield-check', 'primary', 'Verifikasi'],
        'export' => ['download-simple', 'purple', 'Eksport'],
        'permission' => ['lock-key', 'danger', 'Kebenaran'],
    ];

    private const LABELS = [
        'login' => 'Log masuk',
        'logout' => 'Log keluar',
        'login.failed' => 'Log masuk gagal',
        'login.locked' => 'Akaun dikunci',
        'login.suspended' => 'Akaun digantung',
        'sessions.revoked' => 'Tamatkan sesi',
        'user.roles' => 'Tukar peranan',
        'role.permissions' => 'Tukar kebenaran',
        'role.updated' => 'Kemaskini peranan',
        'user.force_password' => 'Paksa tukar kata laluan',
        'user.reset_link' => 'Pautan reset kata laluan',
        'user.created' => 'Cipta pengguna',
        'user.updated' => 'Kemaskini pengguna',
        'payment.verified' => 'Sahkan bayaran',
        'payment.rejected' => 'Tolak bayaran',
        'report.verified' => 'Verify laporan',
        'report.submitted' => 'Hantar laporan',
        'report.generated' => 'Jana laporan',
        'invoice.created' => 'Cipta invois',
        'invoice.payment' => 'Kemaskini bayaran',
        'quotation.created' => 'Cipta quotation',
        'lead.created' => 'Cipta lead',
        'lead.stage' => 'Kemaskini lead',
        'order.stage' => 'Kemaskini peringkat',
        'order.status' => 'Kemaskini status',
        'order.updated' => 'Kemaskini tempahan',
        'certificate.issued' => 'Jana sijil',
        'document.uploaded' => 'Muat naik fail',
        'document.deleted' => 'Padam fail',
        'audit.export' => 'Eksport log audit',
    ];

    public static function type(?string $event): string
    {
        $e = (string) $event;

        return match (true) {
            $e === 'logout' || str_starts_with($e, 'login') || str_starts_with($e, 'sessions.') => 'login',
            str_starts_with($e, 'role.') || in_array($e, ['user.roles', 'user.force_password', 'user.reset_link'], true) => 'permission',
            str_contains($e, 'export') || str_contains($e, 'generated') || str_contains($e, 'waybill') => 'export',
            str_contains($e, 'deleted') || str_contains($e, 'cancel') || str_contains($e, 'rejected') || str_contains($e, 'suspended') => 'delete',
            str_contains($e, 'verified') || str_contains($e, 'paid') || str_contains($e, 'done') || str_contains($e, 'converted') => 'verify',
            str_contains($e, 'created') || str_contains($e, 'uploaded') || str_contains($e, 'issued') || str_contains($e, 'submitted') => 'create',
            default => 'edit',
        };
    }

    public static function label(?string $event): string
    {
        $e = (string) $event;

        return self::LABELS[$e] ?? match (self::type($e)) {
            'create' => 'Cipta rekod',
            'delete' => 'Padam / batal',
            'verify' => 'Pengesahan',
            'export' => 'Eksport data',
            'permission' => 'Kebenaran',
            'login' => 'Sesi',
            default => 'Kemaskini data',
        };
    }

    /** @return array{0: string, 1: string, 2: string} */
    public static function style(?string $event): array
    {
        return self::TYPES[self::type($event)];
    }
}
