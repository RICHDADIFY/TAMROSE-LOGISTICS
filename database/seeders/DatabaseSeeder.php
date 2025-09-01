<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Core static data for Phase 0
        $this->call([
            PortsSeeder::class,
            VesselsSeeder::class,
            LocationsSeeder::class,
            RolesAndAdminSeeder::class, // <— add this
            // RolesSeeder::class, // ← uncomment if you create roles via Spatie
            // UsersSeeder::class, // ← optional: create a default manager/driver/staff
        ]);

        // (Optional) remove the default factory test user if you don't need it:
        // \App\Models\User::factory()->create([
        //     'name'  => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
