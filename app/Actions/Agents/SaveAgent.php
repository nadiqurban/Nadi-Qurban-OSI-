<?php

namespace App\Actions\Agents;

use App\Enums\RoleName;
use App\Enums\Severity;
use App\Enums\UserStatus;
use App\Models\Agent;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;

/**
 * Tambah / Edit Ejen: the agent's login account (User, role Ejen) + profile.
 * The link slug is fixed on creation so links already shared keep working.
 */
class SaveAgent
{
    /**
     * @param  array{code: string, name: string, email: string, phone: ?string, password: ?string, gender: ?string, birth_date: ?string, district: ?string, state: ?string, bank_name: ?string, bank_account_name: ?string, bank_account_no: ?string}  $data
     */
    public function handle(?Agent $agent, array $data, User $actor): Agent
    {
        return DB::transaction(function () use ($agent, $data, $actor) {
            $isNew = $agent === null;
            $user = $agent !== null ? $agent->user : new User(['status' => UserStatus::Active, 'must_change_password' => false]);

            $user->fill(['name' => $data['name'], 'email' => mb_strtolower($data['email']), 'phone' => $data['phone']]);

            if ($data['password']) {
                $user->password = $data['password'];
            }

            $user->save();

            if ($isNew) {
                $user->syncRoles([RoleName::Agent->value]);
            }

            $agent ??= new Agent(['user_id' => $user->id, 'slug' => Agent::makeSlug($data['name'])]);
            $agent->fill([
                'code' => mb_strtoupper($data['code']),
                'gender' => $data['gender'],
                'birth_date' => $data['birth_date'],
                'district' => $data['district'],
                'state' => $data['state'],
                'bank_name' => $data['bank_name'],
                'bank_account_name' => $data['bank_account_name'],
                'bank_account_no' => $data['bank_account_no'],
            ])->save();

            Audit::log(
                $isNew ? 'agent.created' : 'agent.updated',
                $isNew ? "Ejen {$user->name} ({$agent->code}) didaftarkan" : "Maklumat ejen {$user->name} dikemaskini",
                $agent,
                $data['password'] && ! $isNew ? Severity::Warning : Severity::Info,
                ['code' => $agent->code, 'password_changed' => ! $isNew && (bool) $data['password']],
                $actor,
                'agents',
            );

            return $agent->setRelation('user', $user);
        });
    }
}
