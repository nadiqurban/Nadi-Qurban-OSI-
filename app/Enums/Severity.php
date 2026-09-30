<?php

namespace App\Enums;

/** Audit log severity (Audit Log.dc.html tabs: Info / Amaran / Kritikal). */
enum Severity: string
{
    case Info = 'info';
    case Warning = 'amaran';
    case Critical = 'kritikal';

    public function label(): string
    {
        return match ($this) {
            self::Info => 'Info',
            self::Warning => 'Amaran',
            self::Critical => 'Kritikal',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Info => 'info',
            self::Warning => 'warning',
            self::Critical => 'danger',
        };
    }
}
