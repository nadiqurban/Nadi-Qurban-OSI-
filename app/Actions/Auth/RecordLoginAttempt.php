<?php

namespace App\Actions\Auth;

use App\Enums\LoginStatus;
use App\Models\LoginHistory;
use App\Models\User;
use App\Support\UserAgent;

class RecordLoginAttempt
{
    public function handle(?User $user, string $email, LoginStatus $status): LoginHistory
    {
        $ua = UserAgent::parse(request()->userAgent());

        return LoginHistory::query()->create([
            'user_id' => $user?->id,
            'email' => mb_strtolower($email),
            'status' => $status,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 512),
            'browser' => $ua->browser(),
            'platform' => $ua->platform(),
            'device' => $ua->device(),
            'session_id' => $status === LoginStatus::Success ? session()->getId() : null,
        ]);
    }
}
