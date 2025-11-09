<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            VesselRoleAccessSeeder::class,
            CrewPositionSeeder::class, // Run before UserVesselRoleSeeder so positions exist
            CountrySeeder::class, // Run before VatProfileSeeder
            CurrencySeeder::class, // Run before VesselSettingSeeder
            VatProfileSeeder::class, // Run after CountrySeeder
            UserSeeder::class,
            DefaultVesselSeeder::class,
            UserVesselRoleSeeder::class, // This now assigns positions to users
            TransactionCategorySeeder::class,
            VesselSettingSeeder::class, // Run after vessels are created
        ]);

        $withTestData = false;

        if ($this->command && method_exists($this->command, 'hasOption') && $this->command->hasOption('with-test-data')) {
            $withTestData = (bool) $this->command->option('with-test-data');
        }

        // Add test seeders if running in testing environment
        if (app()->environment('testing') || $withTestData) {
            $this->call([
                \Database\Seeders\Test\ComprehensiveTestSeeder::class,
            ]);
        }
    }
}
