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
            UserSeeder::class,
            DefaultVesselSeeder::class,
            UserVesselRoleSeeder::class,
            VatRateSeeder::class,
            TransactionCategorySeeder::class,
            CrewPositionSeeder::class,
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
