<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MovimentationCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delete all existing transaction categories
        // Handle foreign key constraints based on database driver
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // MySQL: Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('transaction_categories')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } else {
            // SQLite or other databases: Delete in correct order or disable foreign keys
            if ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = OFF;');
            }

            // Delete all categories (cascade will handle children)
            DB::table('transaction_categories')->delete();

            if ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            }
        }

        $categories = [
            // Income Categories
            ['name' => 'Charter', 'type' => 'income', 'color' => '#10b981', 'is_system' => true],
            ['name' => 'Freight', 'type' => 'income', 'color' => '#059669', 'is_system' => true],
            ['name' => 'Passenger Transport', 'type' => 'income', 'color' => '#34d399', 'is_system' => true],
            ['name' => 'Cargo Transport', 'type' => 'income', 'color' => '#6ee7b7', 'is_system' => true],
            ['name' => 'Fishing Operations', 'type' => 'income', 'color' => '#047857', 'is_system' => true],
            ['name' => 'Fishing Sale', 'type' => 'income', 'color' => '#065f46', 'is_system' => true],
            ['name' => 'Services', 'type' => 'income', 'color' => '#3b82f6', 'is_system' => true],
            ['name' => 'Towing Services', 'type' => 'income', 'color' => '#2563eb', 'is_system' => true],
            ['name' => 'Salvage Operations', 'type' => 'income', 'color' => '#1d4ed8', 'is_system' => true],
            ['name' => 'Training Services', 'type' => 'income', 'color' => '#60a5fa', 'is_system' => true],
            ['name' => 'Rental Income', 'type' => 'income', 'color' => '#93c5fd', 'is_system' => true],
            ['name' => 'Insurance Claims', 'type' => 'income', 'color' => '#8b5cf6', 'is_system' => true],
            ['name' => 'Subsidies', 'type' => 'income', 'color' => '#7c3aed', 'is_system' => true],
            ['name' => 'Grants', 'type' => 'income', 'color' => '#a78bfa', 'is_system' => true],
            ['name' => 'Interest Income', 'type' => 'income', 'color' => '#c4b5fd', 'is_system' => true],
            ['name' => 'Other Income', 'type' => 'income', 'color' => '#d1d5db', 'is_system' => true],

            // Expense Categories - Operations
            ['name' => 'Fuel', 'type' => 'expense', 'color' => '#ef4444', 'is_system' => true],
            ['name' => 'Diesel', 'type' => 'expense', 'color' => '#dc2626', 'is_system' => true],
            ['name' => 'Lubricants', 'type' => 'expense', 'color' => '#b91c1c', 'is_system' => true],
            ['name' => 'Engine Oil', 'type' => 'expense', 'color' => '#991b1b', 'is_system' => true],
            ['name' => 'Gearbox Oil', 'type' => 'expense', 'color' => '#881337', 'is_system' => true],
            ['name' => 'Hydraulic Oil', 'type' => 'expense', 'color' => '#9f1239', 'is_system' => true],
            ['name' => 'Maintenance', 'type' => 'expense', 'color' => '#f59e0b', 'is_system' => true],
            ['name' => 'Repairs', 'type' => 'expense', 'color' => '#d97706', 'is_system' => true],
            ['name' => 'Hull Maintenance', 'type' => 'expense', 'color' => '#b45309', 'is_system' => true],
            ['name' => 'Engine Repairs', 'type' => 'expense', 'color' => '#92400e', 'is_system' => true],
            ['name' => 'Electrical Repairs', 'type' => 'expense', 'color' => '#78350f', 'is_system' => true],
            ['name' => 'Plumbing Repairs', 'type' => 'expense', 'color' => '#713f12', 'is_system' => true],
            ['name' => 'Paint & Coatings', 'type' => 'expense', 'color' => '#f97316', 'is_system' => true],
            ['name' => 'Anti-fouling', 'type' => 'expense', 'color' => '#ea580c', 'is_system' => true],
            ['name' => 'Docking', 'type' => 'expense', 'color' => '#a855f7', 'is_system' => true],
            ['name' => 'Mooring', 'type' => 'expense', 'color' => '#9333ea', 'is_system' => true],
            ['name' => 'Port Fees', 'type' => 'expense', 'color' => '#7e22ce', 'is_system' => true],
            ['name' => 'Pilotage', 'type' => 'expense', 'color' => '#6b21a8', 'is_system' => true],
            ['name' => 'Tugboat Services', 'type' => 'expense', 'color' => '#581c87', 'is_system' => true],
            ['name' => 'Wharfage', 'type' => 'expense', 'color' => '#c084fc', 'is_system' => true],

            // Expense Categories - Personnel
            ['name' => 'Salaries', 'type' => 'expense', 'color' => '#ec4899', 'is_system' => true],
            ['name' => 'Wages', 'type' => 'expense', 'color' => '#db2777', 'is_system' => true],
            ['name' => 'Crew Salaries', 'type' => 'expense', 'color' => '#be185d', 'is_system' => true],
            ['name' => 'Bonuses', 'type' => 'expense', 'color' => '#9f1239', 'is_system' => true],
            ['name' => 'Overtime', 'type' => 'expense', 'color' => '#831843', 'is_system' => true],
            ['name' => 'Training', 'type' => 'expense', 'color' => '#f472b6', 'is_system' => true],
            ['name' => 'Certifications', 'type' => 'expense', 'color' => '#fb7185', 'is_system' => true],
            ['name' => 'Travel Expenses', 'type' => 'expense', 'color' => '#fda4af', 'is_system' => true],
            ['name' => 'Accommodation', 'type' => 'expense', 'color' => '#fecdd3', 'is_system' => true],

            // Expense Categories - Insurance & Legal
            ['name' => 'Insurance', 'type' => 'expense', 'color' => '#6366f1', 'is_system' => true],
            ['name' => 'Hull Insurance', 'type' => 'expense', 'color' => '#4f46e5', 'is_system' => true],
            ['name' => 'P&I Insurance', 'type' => 'expense', 'color' => '#4338ca', 'is_system' => true],
            ['name' => 'Crew Insurance', 'type' => 'expense', 'color' => '#3730a3', 'is_system' => true],
            ['name' => 'Cargo Insurance', 'type' => 'expense', 'color' => '#312e81', 'is_system' => true],
            ['name' => 'Legal Fees', 'type' => 'expense', 'color' => '#818cf8', 'is_system' => true],
            ['name' => 'Consulting Fees', 'type' => 'expense', 'color' => '#a5b4fc', 'is_system' => true],
            ['name' => 'Accounting Fees', 'type' => 'expense', 'color' => '#c7d2fe', 'is_system' => true],

            // Expense Categories - Fees & Licenses
            ['name' => 'Fees & Licenses', 'type' => 'expense', 'color' => '#14b8a6', 'is_system' => true],
            ['name' => 'Registration Fees', 'type' => 'expense', 'color' => '#0d9488', 'is_system' => true],
            ['name' => 'License Fees', 'type' => 'expense', 'color' => '#0f766e', 'is_system' => true],
            ['name' => 'Fishing License', 'type' => 'expense', 'color' => '#115e59', 'is_system' => true],
            ['name' => 'Fishing Inspection', 'type' => 'expense', 'color' => '#0f766e', 'is_system' => true],
            ['name' => 'Customs Fees', 'type' => 'expense', 'color' => '#134e4a', 'is_system' => true],
            ['name' => 'Import/Export Fees', 'type' => 'expense', 'color' => '#2dd4bf', 'is_system' => true],
            ['name' => 'Inspection Fees', 'type' => 'expense', 'color' => '#5eead4', 'is_system' => true],
            ['name' => 'Classification Fees', 'type' => 'expense', 'color' => '#99f6e4', 'is_system' => true],
            ['name' => 'Taxes', 'type' => 'expense', 'color' => '#ccfbf1', 'is_system' => true],
            ['name' => 'VAT', 'type' => 'expense', 'color' => '#14b8a6', 'is_system' => true],
            ['name' => 'Income Tax', 'type' => 'expense', 'color' => '#0d9488', 'is_system' => true],

            // Expense Categories - Supplies & Equipment
            ['name' => 'Food & Provisions', 'type' => 'expense', 'color' => '#f97316', 'is_system' => true],
            ['name' => 'Groceries', 'type' => 'expense', 'color' => '#ea580c', 'is_system' => true],
            ['name' => 'Supplies', 'type' => 'expense', 'color' => '#fb923c', 'is_system' => true],
            ['name' => 'Safety Equipment', 'type' => 'expense', 'color' => '#fdba74', 'is_system' => true],
            ['name' => 'Life Jackets', 'type' => 'expense', 'color' => '#fec163', 'is_system' => true],
            ['name' => 'Fire Extinguishers', 'type' => 'expense', 'color' => '#fed7aa', 'is_system' => true],
            ['name' => 'Navigation Equipment', 'type' => 'expense', 'color' => '#22c55e', 'is_system' => true],
            ['name' => 'GPS Systems', 'type' => 'expense', 'color' => '#16a34a', 'is_system' => true],
            ['name' => 'Radar Equipment', 'type' => 'expense', 'color' => '#15803d', 'is_system' => true],
            ['name' => 'Communication Equipment', 'type' => 'expense', 'color' => '#4ade80', 'is_system' => true],
            ['name' => 'Radio Equipment', 'type' => 'expense', 'color' => '#86efac', 'is_system' => true],
            ['name' => 'Satellite Communication', 'type' => 'expense', 'color' => '#bbf7d0', 'is_system' => true],
            ['name' => 'Engine Parts', 'type' => 'expense', 'color' => '#ef4444', 'is_system' => true],
            ['name' => 'Spare Parts', 'type' => 'expense', 'color' => '#dc2626', 'is_system' => true],
            ['name' => 'Ropes & Cables', 'type' => 'expense', 'color' => '#64748b', 'is_system' => true],
            ['name' => 'Anchors & Chains', 'type' => 'expense', 'color' => '#475569', 'is_system' => true],
            ['name' => 'Fishing Gear', 'type' => 'expense', 'color' => '#334155', 'is_system' => true],
            ['name' => 'Nets', 'type' => 'expense', 'color' => '#1e293b', 'is_system' => true],

            // Expense Categories - Utilities & Services
            ['name' => 'Utilities', 'type' => 'expense', 'color' => '#06b6d4', 'is_system' => true],
            ['name' => 'Electricity', 'type' => 'expense', 'color' => '#0891b2', 'is_system' => true],
            ['name' => 'Water', 'type' => 'expense', 'color' => '#0e7490', 'is_system' => true],
            ['name' => 'Internet', 'type' => 'expense', 'color' => '#155e75', 'is_system' => true],
            ['name' => 'Phone', 'type' => 'expense', 'color' => '#164e63', 'is_system' => true],
            ['name' => 'Security Services', 'type' => 'expense', 'color' => '#22d3ee', 'is_system' => true],
            ['name' => 'Cleaning Services', 'type' => 'expense', 'color' => '#67e8f9', 'is_system' => true],
            ['name' => 'Waste Management', 'type' => 'expense', 'color' => '#a5f3fc', 'is_system' => true],
            ['name' => 'Laundry Services', 'type' => 'expense', 'color' => '#cffafe', 'is_system' => true],

            // Expense Categories - Marketing & Administration
            ['name' => 'Marketing', 'type' => 'expense', 'color' => '#8b5cf6', 'is_system' => true],
            ['name' => 'Advertising', 'type' => 'expense', 'color' => '#7c3aed', 'is_system' => true],
            ['name' => 'Website Maintenance', 'type' => 'expense', 'color' => '#a78bfa', 'is_system' => true],
            ['name' => 'Office Supplies', 'type' => 'expense', 'color' => '#c4b5fd', 'is_system' => true],
            ['name' => 'Bank Charges', 'type' => 'expense', 'color' => '#e9d5ff', 'is_system' => true],
            ['name' => 'Transaction Fees', 'type' => 'expense', 'color' => '#f3e8ff', 'is_system' => true],

            // Expense Categories - Other
            ['name' => 'General', 'type' => 'expense', 'color' => '#64748b', 'is_system' => true],
            ['name' => 'Other Expenses', 'type' => 'expense', 'color' => '#64748b', 'is_system' => true],
            ['name' => 'Miscellaneous', 'type' => 'expense', 'color' => '#475569', 'is_system' => true],
        ];

        foreach ($categories as $category) {
            \App\Models\MovimentationCategory::updateOrCreate(
                ['name' => $category['name'], 'type' => $category['type']],
                $category
            );
        }
    }
}
