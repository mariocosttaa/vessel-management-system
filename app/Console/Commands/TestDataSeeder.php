<?php

namespace App\Console\Commands;

use Database\Seeders\Test\ComprehensiveTestSeeder;
use Database\Seeders\Test\UserTestSeeder;
use Database\Seeders\Test\VesselTestSeeder;
use Database\Seeders\Test\PermissionTestSeeder;
use Database\Seeders\Test\TransactionTestSeeder;
use Illuminate\Console\Command;

class TestDataSeeder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:seed {--fresh : Run fresh migrations before seeding} {--users : Only seed test users} {--vessels : Only seed test vessels} {--permissions : Only seed test permissions} {--transactions : Only seed test transactions} {--comprehensive : Run comprehensive test seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed test data for comprehensive testing of user permissions and vessel management';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Starting Test Data Seeding');
        $this->info('============================');

        // Check if fresh migration is requested
        if ($this->option('fresh')) {
            $this->warn('⚠️  Running fresh migrations...');
            $this->call('migrate:fresh');
            $this->call('db:seed', ['--class' => 'RoleSeeder']);
            $this->call('db:seed', ['--class' => 'VesselRoleAccessSeeder']);
        }

        // Run specific seeders based on options
        if ($this->option('comprehensive')) {
            $this->runComprehensiveSeeding();
        } elseif ($this->option('users')) {
            $this->runUserSeeding();
        } elseif ($this->option('vessels')) {
            $this->runVesselSeeding();
        } elseif ($this->option('permissions')) {
            $this->runPermissionSeeding();
        } elseif ($this->option('transactions')) {
            $this->runTransactionSeeding();
        } else {
            $this->runAllSeeding();
        }

        $this->info('✅ Test data seeding completed!');
        $this->displayNextSteps();
    }

    private function runComprehensiveSeeding(): void
    {
        $this->info('🚀 Running comprehensive test seeding...');
        $this->call('db:seed', ['--class' => ComprehensiveTestSeeder::class]);
    }

    private function runUserSeeding(): void
    {
        $this->info('👥 Seeding test users...');
        $this->call('db:seed', ['--class' => UserTestSeeder::class]);
    }

    private function runVesselSeeding(): void
    {
        $this->info('🚢 Seeding test vessels...');
        $this->call('db:seed', ['--class' => VesselTestSeeder::class]);
    }

    private function runPermissionSeeding(): void
    {
        $this->info('🎭 Seeding test permissions...');
        $this->call('db:seed', ['--class' => PermissionTestSeeder::class]);
    }

    private function runTransactionSeeding(): void
    {
        $this->info('💰 Seeding test transactions...');
        $this->call('db:seed', ['--class' => TransactionTestSeeder::class]);
    }

    private function runAllSeeding(): void
    {
        $this->info('🔄 Running all test seeders...');
        $this->call('db:seed', ['--class' => PermissionTestSeeder::class]);
        $this->call('db:seed', ['--class' => VesselTestSeeder::class]);
        $this->call('db:seed', ['--class' => UserTestSeeder::class]);
    }

    private function displayNextSteps(): void
    {
        $this->info('');
        $this->info('🎯 NEXT STEPS:');
        $this->info('==============');
        $this->info('1. Start the development server: php artisan serve');
        $this->info('2. Visit: http://localhost:8000');
        $this->info('3. Test with these credentials:');
        $this->info('');

        $this->displayTestCredentials();

        $this->info('');
        $this->info('🧪 TEST SCENARIOS:');
        $this->info('• Login with different user types');
        $this->info('• Test vessel creation permissions');
        $this->info('• Test vessel access based on roles');
        $this->info('• Test edit/delete permissions');
        $this->info('• Test multi-vessel access');
        $this->info('');
        $this->info('📊 Check the database for:');
        $this->info('• users table - all test users');
        $this->info('• vessels table - all test vessels');
        $this->info('• vessel_users table - user-vessel relationships');
        $this->info('• vessel_role_accesses table - permission definitions');
    }

    private function displayTestCredentials(): void
    {
        $credentials = [
            ['Email', 'Type', 'Description'],
            ['paid-admin@test.com', 'Paid System', 'Can create vessels, full admin access'],
            ['paid-manager@test.com', 'Paid System', 'Can create vessels, manager permissions'],
            ['paid-viewer@test.com', 'Paid System', 'Can create vessels, viewer permissions'],
            ['employee-normal@test.com', 'Employee', 'Normal user, view-only access'],
            ['employee-moderator@test.com', 'Employee', 'Moderator, basic edit access'],
            ['employee-supervisor@test.com', 'Employee', 'Supervisor, advanced edit access'],
            ['employee-admin@test.com', 'Employee', 'Administrator, full vessel control'],
            ['mixed-admin@test.com', 'Paid System', 'Mixed permissions across vessels'],
            ['mixed-manager@test.com', 'Paid System', 'Mixed permissions across vessels'],
            ['multi-vessel@test.com', 'Employee', 'Multiple vessels, different roles'],
            ['no-vessels@test.com', 'Employee', 'No vessel access (edge case)'],
            ['inactive-access@test.com', 'Employee', 'Inactive vessel access (edge case)'],
        ];

        $this->table(
            ['Email', 'Type', 'Description'],
            array_slice($credentials, 1) // Remove header row
        );

        $this->info('🔑 Password for all test users: password');
    }
}

