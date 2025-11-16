<?php

namespace App\Console\Commands;

use Database\Seeders\CountrySeeder;
use Database\Seeders\CrewPositionSeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\VatProfileSeeder;
use Database\Seeders\VesselRoleAccessSeeder;
use Illuminate\Console\Command;

class SeedProduction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:seed:prod';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed production data: Countries, Currencies, Crew Positions, VAT Profiles, and Vessel Role Access';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🌱 Starting Production Data Seeding');
        $this->info('====================================');

        $seeders = [
            VesselRoleAccessSeeder::class, // Must run first (CrewPositionSeeder may reference it)
            CountrySeeder::class,          // Must run before VatProfileSeeder
            CurrencySeeder::class,
            CrewPositionSeeder::class,     // May reference VesselRoleAccess
            VatProfileSeeder::class,        // Must run after CountrySeeder
        ];

        foreach ($seeders as $seeder) {
            $seederName = class_basename($seeder);
            $this->info("📦 Seeding {$seederName}...");

            try {
                $this->call('db:seed', ['--class' => $seeder]);
                $this->info("✅ {$seederName} completed");
            } catch (\Exception $e) {
                $this->error("❌ {$seederName} failed: " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $this->info('');
        $this->info('✅ Production data seeding completed successfully!');

        return Command::SUCCESS;
    }
}

