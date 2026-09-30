<?php

namespace App\Support;

use App\Enums\Severity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

/**
 * Single entry point for the audit trail (activity_log + severity + IP).
 * Never log secrets (passwords, tokens, 2FA codes).
 */
final class Audit
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public static function log(
        string $event,
        string $description,
        ?Model $subject = null,
        Severity $severity = Severity::Info,
        array $properties = [],
        ?User $causer = null,
        string $logName = 'default',
    ): void {
        $logger = activity($logName)
            ->event($event)
            ->withProperties($properties)
            ->tap(function (Activity $activity) use ($severity) {
                $activity->setAttribute('severity', $severity->value);
                $activity->setAttribute('ip_address', request()->ip());
            });

        if ($subject) {
            $logger->performedOn($subject);
        }

        if ($causer) {
            $logger->causedBy($causer);
        }

        $logger->log($description);
    }
}
