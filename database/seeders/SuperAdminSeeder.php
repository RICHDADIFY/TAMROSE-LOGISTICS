<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $u = User::firstOrCreate(
            ['email' => 'itsupport@tamrose.com'],
            ['name' => 'System Super Admin', 'password' => bcrypt('Lionking@1688')]
        );

        $u->forceFill(['is_super_admin' => true])->save();

        // If you use Spatie roles, you could also:
        // $u->assignRole('Admin');
    }
}
