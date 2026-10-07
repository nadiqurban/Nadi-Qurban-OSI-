<?php

namespace App\Actions\Agents;

use App\Enums\Severity;
use App\Models\Agent;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Buang ejen: only when no order came through their link (otherwise deactivate). */
class DeleteAgent
{
    public function handle(Agent $agent, User $actor): void
    {
        if ($agent->orders()->exists()) {
            throw ValidationException::withMessages([
                'agent' => "Ejen {$agent->user->name} mempunyai tempahan — nyahaktifkan sahaja supaya rekod komisen kekal.",
            ]);
        }

        DB::transaction(function () use ($agent, $actor) {
            Audit::log('agent.deleted', "Ejen {$agent->user->name} ({$agent->code}) dibuang", $agent, Severity::Critical, ['email' => $agent->user->email], $actor, 'agents');

            DB::table(config('session.table', 'sessions'))->where('user_id', $agent->user_id)->delete();
            $agent->user->delete();   // cascades to the agent profile and clicks
        });
    }
}
