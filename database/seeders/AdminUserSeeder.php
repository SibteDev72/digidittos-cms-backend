<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the admin user.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@digidittos.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'role' => 'super_admin',
            ]
        );

        // Assign the super_admin role
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        if ($superAdminRole) {
            $admin->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }
    }
}
