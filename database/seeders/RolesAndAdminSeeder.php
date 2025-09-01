<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Schema;

class RolesAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Ensure roles exist
        foreach (['Super Admin','Logistics Manager','Driver','Staff'] as $name) {
            Role::findOrCreate($name, 'web');
        }

        // 2) Configure your key emails (use .env so you don’t hardcode in git)
        $superAdminEmail = env('SUPERADMIN_EMAIL', 'ieze@tamrose.com');         // optional
        $managerEmail    = env('LOGISTICS_MANAGER_EMAIL', 'ieze@tamrose.com');  // your manager
        $driverEmails    = [
            'ifeanyieze50@gmail.com', // driver
        ];

        // 3) Super Admin (optional)
        if ($u = User::where('email', $superAdminEmail)->first()) {
            $u->syncRoles(['Super Admin', 'Logistics Manager']); // if you want both
            if (Schema::hasColumn('users', 'is_super_admin')) $u->forceFill(['is_super_admin' => true])->save();
            if (Schema::hasColumn('users', 'is_manager'))     $u->forceFill(['is_manager' => true])->save();
        }

        // 4) Manager (only Logistics Manager)
        if ($u = User::where('email', $managerEmail)->first()) {
            $u->syncRoles(['Logistics Manager']);
            if (Schema::hasColumn('users', 'is_manager')) $u->forceFill(['is_manager' => true])->save();
            if (Schema::hasColumn('users', 'is_super_admin')) $u->forceFill(['is_super_admin' => false])->save();
        }

        // 5) Drivers (Driver role only; clear manager/admin flags)
        foreach ($driverEmails as $email) {
            if ($u = User::where('email', $email)->first()) {
                $u->syncRoles(['Driver']); // IMPORTANT: sync (overwrites any stray roles)
                $changes = [];
                if (Schema::hasColumn('users', 'is_manager'))     $changes['is_manager'] = false;
                if (Schema::hasColumn('users', 'is_super_admin')) $changes['is_super_admin'] = false;
                if (!empty($changes)) $u->forceFill($changes)->save();
            }
        }

        // Optional sanitation: if your app still checks is_manager anywhere,
        // keep it aligned with roles to avoid confusion:
        if (Schema::hasColumn('users', 'is_manager')) {
            User::role('Logistics Manager')->update(['is_manager' => true]);
            User::role('Driver')->update(['is_manager' => false]);
        }
    }
}
