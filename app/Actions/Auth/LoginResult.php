<?php

namespace App\Actions\Auth;

enum LoginResult
{
    case Success;
    case Invalid;
    case Locked;
    case Suspended;
    case Throttled;
    case TwoFactorRequired;
}
