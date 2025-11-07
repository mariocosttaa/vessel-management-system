<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CrewPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            ['name' => 'Capitão', 'description' => 'Comandante da embarcação'],
            ['name' => 'Imediato', 'description' => 'Segundo no comando'],
            ['name' => 'Marinheiro', 'description' => 'Tripulação geral'],
            ['name' => 'Mecânico', 'description' => 'Responsável pela manutenção'],
            ['name' => 'Cozinheiro', 'description' => 'Responsável pela alimentação'],
        ];

        foreach ($positions as $position) {
            \App\Models\CrewPosition::updateOrCreate(
                ['name' => $position['name']],
                $position
            );
        }
    }
}
