<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'admin', 'description' => 'Administrador do sistema'],
            ['name' => 'manager', 'description' => 'Gestor financeiro'],
            ['name' => 'viewer', 'description' => 'Visualizador apenas'],
        ];

        foreach ($roles as $role) {
            \App\Models\Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
