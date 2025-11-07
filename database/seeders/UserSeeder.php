<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $adminRole = Role::where('name', 'admin')->first();
        $managerRole = Role::where('name', 'manager')->first();
        $viewerRole = Role::where('name', 'viewer')->first();

        $usersToSeed = [
            [
                'email' => 'admin@example.com',
                'name' => 'Admin User',
                'role' => $adminRole,
                'user_type' => 'paid_system',
                'login_permitted' => true,
            ],
            [
                'email' => 'manager@example.com',
                'name' => 'Manager User',
                'role' => $managerRole,
                'user_type' => 'employee_of_vessel',
                'login_permitted' => true,
            ],
            [
                'email' => 'viewer@example.com',
                'name' => 'Viewer User',
                'role' => $viewerRole,
                'user_type' => 'employee_of_vessel',
                'login_permitted' => true,
            ],
            [
                'email' => 'test@example.com',
                'name' => 'Test User',
                'role' => $adminRole,
                'user_type' => 'paid_system',
                'login_permitted' => true,
            ],
        ];

        foreach ($usersToSeed as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'user_type' => $userData['user_type'],
                    'email_verified_at' => $now,
                    'login_permitted' => $userData['login_permitted'],
                ]
            );

            if ($userData['role']) {
                $user->roles()->syncWithoutDetaching([$userData['role']->id]);
            }
        }
    }
}
