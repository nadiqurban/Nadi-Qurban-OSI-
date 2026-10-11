<?php

namespace App\Actions\Agents;

use App\Enums\RoleName;
use App\Enums\Severity;
use App\Enums\UserStatus;
use App\Models\Agent;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Enums\ImageDriver;
use Spatie\Image\Image;

/**
 * Pendaftaran Ejen (public form /daftar-ejen): creates the agent's account as
 * "Menunggu" — the login stays blocked until HQ approves it in Pengurusan Ejen.
 * The photo is cropped to a 320×320 JPEG (passport style) before it is stored.
 */
class RegisterAgent
{
    /**
     * @param  array{name: string, email: string, phone: string, password: string, gender: string, birth_date: ?string, district: ?string, state: string, bank_name: string, bank_account_name: ?string, bank_account_no: ?string}  $data
     */
    public function handle(array $data, UploadedFile $photo): Agent
    {
        $jpeg = tempnam(sys_get_temp_dir(), 'nq-ejen').'.jpg';
        Image::useImageDriver(ImageDriver::Gd)->loadFile($photo->getRealPath())
            ->fit(Fit::Crop, 320, 320)->quality(75)->save($jpeg);

        try {
            return DB::transaction(function () use ($data, $jpeg) {
                $user = new User(['name' => $data['name'], 'email' => mb_strtolower($data['email']), 'phone' => $data['phone'],
                    'status' => UserStatus::Suspended, 'must_change_password' => false]);
                $user->password = $data['password'];
                $user->save();
                $user->syncRoles([RoleName::Agent->value]);

                $agent = Agent::query()->create([
                    'user_id' => $user->id,
                    'code' => Agent::nextCode($data['name']),
                    'slug' => Agent::makeSlug($data['name']),
                    'gender' => $data['gender'],
                    'birth_date' => $data['birth_date'],
                    'district' => $data['district'],
                    'state' => $data['state'],
                    'bank_name' => $data['bank_name'],
                    'bank_account_name' => $data['bank_account_name'],
                    'bank_account_no' => $data['bank_account_no'],
                    'registration_status' => Agent::PENDING,
                ]);

                $agent->addMedia($jpeg)->preservingOriginal()->usingFileName('gambar-'.$agent->code.'.jpg')->toMediaCollection('photo');

                Audit::log('agent.registered', "Pendaftaran ejen baharu: {$user->name} ({$agent->code})", $agent, Severity::Info,
                    ['code' => $agent->code], null, 'agents');

                return $agent->setRelation('user', $user);
            });
        } finally {
            @unlink($jpeg);
        }
    }
}
