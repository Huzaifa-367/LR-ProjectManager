<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            DemoUserSeeder::class,
        ]);

        if (filter_var(env('SEED_TCM_DEMO', false), FILTER_VALIDATE_BOOL)) {
            $this->call(TcmCommandCentreDemoSeeder::class);
        }
    }
}
