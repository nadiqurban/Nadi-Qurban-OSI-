<?php

namespace App\Actions\Agents;

use App\Enums\Severity;
use App\Enums\UserStatus;
use App\Models\Agent;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pendaftaran Baharu › Lulus / Tolak. Lulus activates the agent's login (Portal Ejen);
 * Tolak keeps it blocked and marks the registration "Ditolak".
 */
class ReviewAgentRegistration
{
    public function handle(Agent $agent, bool $approve, User $actor): void
    {
        if (! $agent->isPending()) {
            throw ValidationException::withMessages(['agent' => "Pendaftaran {$agent->user->name} sudah disemak."]);
        }

        DB::transaction(function () use ($agent, $approve, $actor) {
            $agent->forceFill(['registration_status' => $approve ? null : Agent::REJECTED])->save();
            $agent->user->forceFill(['status' => $approve ? UserStatus::Active : UserStatus::Suspended])->save();

            Audit::log($approve ? 'agent.approved' : 'agent.rejected',
                ($approve ? 'Pendaftaran ejen diluluskan: ' : 'Pendaftaran ejen ditolak: ')."{$agent->user->name} ({$agent->code})",
                $agent, $approve ? Severity::Info : Severity::Warning, ['code' => $agent->code], $actor, 'agents');
        });
    }
}
