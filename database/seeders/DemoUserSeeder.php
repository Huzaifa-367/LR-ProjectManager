<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public const PASSWORD = '12345678';

    public function run(): void
    {
        $hash = Hash::make(self::PASSWORD);

        $this->seedPlatformUser('Admin', 'admin@gmail.com', 'super_admin', $hash);
        $this->seedPlatformUser('Platform Admin', 'platform@gmail.com', 'platform_admin', $hash);

        $this->seedUser('Huzaifa', 'huzaifa@atomcamp.com', $hash);
        $this->seedUser('Rafay', 'rafay@atomcamp.com', $hash);
        $this->seedUser('Naveed', 'naveed@atomcamp.com', $hash);
        $this->seedUser('Sarmad', 'sarmad@atomcamp.com', $hash);
    }

    private function seedPlatformUser(string $name, string $email, string $role, string $hash): User
    {
        $user = $this->seedUser($name, $email, $hash);

        if (! $user->hasRole($role)) {
            $user->syncRoles([$role]);
        }

        return $user;
    }

    private function seedUser(string $name, string $email, string $hash): User
    {
        /** @var User $user */
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $hash,
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        return $user;
    }
}
