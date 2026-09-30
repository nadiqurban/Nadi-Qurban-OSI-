<?php

namespace App\Enums;

/** Kaedah Akad (Lafaz Akad.dc.html) + Pukal for bulk akad. */
enum AkadMethod: string
{
    case Phone = 'telefon';
    case WhatsApp = 'whatsapp';
    case InPerson = 'bersemuka';
    case Bulk = 'pukal';

    public function label(): string
    {
        return match ($this) {
            self::Phone => 'Telefon',
            self::WhatsApp => 'WhatsApp',
            self::InPerson => 'Bersemuka',
            self::Bulk => 'Pukal',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Phone => 'phone',
            self::WhatsApp => 'whatsapp-logo',
            self::InPerson => 'users',
            self::Bulk => 'stack',
        };
    }

    /**
     * Methods offered in the akad modal.
     *
     * @return list<self>
     */
    public static function selectable(): array
    {
        return [self::Phone, self::WhatsApp, self::InPerson];
    }
}
