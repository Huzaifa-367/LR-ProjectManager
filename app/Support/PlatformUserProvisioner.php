<?php

namespace App\Support;

use App\Mail\UserAccountCreatedMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class PlatformUserProvisioner
{
    /**
     * @param  array{name: string, email: string, role: string}  $input
     */
    public function create(array $input): User
    {
        $temporaryPassword = Str::password(12);

        $user = User::query()->create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $temporaryPassword,
            'is_active' => true,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $user->syncRoles([$input['role']]);

        app(OrganizationMemberLinker::class)->linkPendingMembershipsForUser($user);

        Mail::to($user)->send(new UserAccountCreatedMail(
            user: $user,
            temporaryPassword: $temporaryPassword,
            loginUrl: route('login'),
        ));

        return $user;
    }
}
