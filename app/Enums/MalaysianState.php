<?php

namespace App\Enums;

/** The 14 negeri in the new-order form, in design order (Tempahan & Pelanggan.dc.html). */
enum MalaysianState: string
{
    case Selangor = 'Selangor';
    case KualaLumpur = 'Kuala Lumpur';
    case Johor = 'Johor';
    case PulauPinang = 'Pulau Pinang';
    case Perak = 'Perak';
    case Kedah = 'Kedah';
    case Kelantan = 'Kelantan';
    case Terengganu = 'Terengganu';
    case Pahang = 'Pahang';
    case NegeriSembilan = 'Negeri Sembilan';
    case Melaka = 'Melaka';
    case Perlis = 'Perlis';
    case Sabah = 'Sabah';
    case Sarawak = 'Sarawak';

    public function label(): string
    {
        return $this->value;
    }
}
