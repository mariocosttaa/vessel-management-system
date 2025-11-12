<?php

namespace Database\Seeders;

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

        $usersToSeed = [
            [
                'email' => 'admin@example.com',
                'name' => 'Admin User',
                'user_type' => 'paid_system',
                'login_permitted' => true,
            ],
            [
                'email' => 'manager@example.com',
                'name' => 'Manager User',
                'user_type' => 'employee_of_vessel',
                'login_permitted' => true,
            ],
            [
                'email' => 'viewer@example.com',
                'name' => 'Viewer User',
                'user_type' => 'employee_of_vessel',
                'login_permitted' => true,
            ],
            [
                'email' => 'test@example.com',
                'name' => 'Test User',
                'user_type' => 'paid_system',
                'login_permitted' => true,
            ],
        ];

        foreach ($usersToSeed as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'user_type' => $userData['user_type'],
                    'email_verified_at' => $now,
                    'login_permitted' => $userData['login_permitted'],
                ]
            );
        }
    }
}
