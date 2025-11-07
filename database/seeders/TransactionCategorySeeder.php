<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Receitas
            ['name' => 'Fretamento', 'type' => 'income', 'color' => '#10b981', 'is_system' => true],
            ['name' => 'Serviços', 'type' => 'income', 'color' => '#3b82f6', 'is_system' => true],
            ['name' => 'Outras Receitas', 'type' => 'income', 'color' => '#8b5cf6', 'is_system' => true],

            // Despesas
            ['name' => 'Combustível', 'type' => 'expense', 'color' => '#ef4444', 'is_system' => true],
            ['name' => 'Manutenção', 'type' => 'expense', 'color' => '#f59e0b', 'is_system' => true],
            ['name' => 'Salários', 'type' => 'expense', 'color' => '#ec4899', 'is_system' => true],
            ['name' => 'Seguros', 'type' => 'expense', 'color' => '#6366f1', 'is_system' => true],
            ['name' => 'Taxas e Licenças', 'type' => 'expense', 'color' => '#14b8a6', 'is_system' => true],
            ['name' => 'Alimentação', 'type' => 'expense', 'color' => '#f97316', 'is_system' => true],
            ['name' => 'Docagem', 'type' => 'expense', 'color' => '#a855f7', 'is_system' => true],
            ['name' => 'Outras Despesas', 'type' => 'expense', 'color' => '#64748b', 'is_system' => true],
        ];

        foreach ($categories as $category) {
            \App\Models\TransactionCategory::updateOrCreate(
                ['name' => $category['name'], 'type' => $category['type']],
                $category
            );
        }
    }
}
