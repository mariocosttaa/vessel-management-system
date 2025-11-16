<?php

namespace Database\Seeders\Test;

use App\Models\Movimentation;
use App\Models\Vessel;
use App\Models\MovimentationCategory;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VatProfile;
use App\Models\VesselSetting;
use App\Actions\MoneyAction;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MovementTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('💰 Creating test movements with historical data...');

        // Get available data
        $vessels = Vessel::where('status', 'active')->get();
        if ($vessels->isEmpty()) {
            $this->command->warn('No active vessels found. Please create vessels first.');
            return;
        }

        $categories = MovimentationCategory::all();
        if ($categories->isEmpty()) {
            $this->command->warn('No movement categories found. Please run MovimentationCategorySeeder first.');
            return;
        }

        $suppliers = Supplier::all();
        $users = User::whereNotNull('email_verified_at')->get();
        if ($users->isEmpty()) {
            $this->command->warn('No verified users found. Please create users first.');
            return;
        }

        // Get VAT profiles
        $vatProfiles = VatProfile::active()->get();
        $defaultVatProfile = VatProfile::where('is_default', true)->first();

        // Get income and expense categories
        $incomeCategories = $categories->where('type', 'income');
        $expenseCategories = $categories->where('type', 'expense');

        $movementTypes = ['income', 'expense'];
        $statuses = ['completed', 'pending', 'cancelled'];

        // Movement descriptions
        $incomeDescriptions = [
            'Cargo delivery payment',
            'Passenger ticket sales',
            'Charter fee',
            'Fishing catch sale',
            'Tourism revenue',
            'Freight income',
            'Service fee',
            'Rental income',
        ];

        $expenseDescriptions = [
            'Fuel purchase',
            'Port fees',
            'Maintenance costs',
            'Crew salary',
            'Insurance payment',
            'Food supplies',
            'Equipment purchase',
            'Repair costs',
            'Docking fees',
            'Cleaning services',
        ];

        $createdCount = 0;
        $now = Carbon::now();

        // Create movements for current month
        $this->command->info('Creating movements for current month...');
        $createdCount += $this->createMovementsForMonth(
            $now->year,
            $now->month,
            $vessels,
            $incomeCategories,
            $expenseCategories,
            $suppliers,
            $users,
            $vatProfiles,
            $defaultVatProfile,
            $movementTypes,
            $statuses,
            $incomeDescriptions,
            $expenseDescriptions,
            15 // 15 movements for current month
        );

        // Create movements for previous months (last 4 months)
        for ($i = 1; $i <= 4; $i++) {
            $date = $now->copy()->subMonths($i);
            $this->command->info("Creating movements for {$date->format('F Y')}...");
            $createdCount += $this->createMovementsForMonth(
                $date->year,
                $date->month,
                $vessels,
                $incomeCategories,
                $expenseCategories,
                $suppliers,
                $users,
                $vatProfiles,
                $defaultVatProfile,
                $movementTypes,
                $statuses,
                $incomeDescriptions,
                $expenseDescriptions,
                rand(10, 20) // Random number of movements per month
            );
        }

        // Create movements for previous years (last 2 years)
        for ($year = $now->year - 1; $year >= $now->year - 2; $year--) {
            // Create movements for 3 random months in each year
            $months = collect(range(1, 12))->shuffle()->take(3);
            foreach ($months as $month) {
                $date = Carbon::create($year, $month, 1);
                $this->command->info("Creating movements for {$date->format('F Y')}...");
                $createdCount += $this->createMovementsForMonth(
                    $year,
                    $month,
                    $vessels,
                    $incomeCategories,
                    $expenseCategories,
                    $suppliers,
                    $users,
                    $vatProfiles,
                    $defaultVatProfile,
                    $movementTypes,
                    $statuses,
                    $incomeDescriptions,
                    $expenseDescriptions,
                    rand(8, 15) // Random number of movements per month
                );
            }
        }

        $this->command->info("✅ Created {$createdCount} test movements successfully!");
        $this->command->info('Movement history is now available for testing.');
    }

    /**
     * Create movements for a specific month and year.
     */
    private function createMovementsForMonth(
        int $year,
        int $month,
        $vessels,
        $incomeCategories,
        $expenseCategories,
        $suppliers,
        $users,
        $vatProfiles,
        $defaultVatProfile,
        array $movementTypes,
        array $statuses,
        array $incomeDescriptions,
        array $expenseDescriptions,
        int $count
    ): int {
        $created = 0;
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        for ($i = 0; $i < $count; $i++) {
            $vessel = $vessels->random();
            $user = $users->random();
            $type = $movementTypes[array_rand($movementTypes)];
            $status = $statuses[array_rand($statuses)];

            // Select category based on type
            if ($type === 'income') {
                $category = $incomeCategories->isNotEmpty() ? $incomeCategories->random() : null;
                $description = $incomeDescriptions[array_rand($incomeDescriptions)];
                $vatProfile = $vatProfiles->isNotEmpty() ? $vatProfiles->random() : $defaultVatProfile;
            } else {
                // Expense
                $category = $expenseCategories->isNotEmpty() ? $expenseCategories->random() : null;
                $description = $expenseDescriptions[array_rand($expenseDescriptions)];
                $vatProfile = null; // Expenses don't have VAT
            }

            if (!$category) {
                continue; // Skip if no category available
            }

            // Random date within the month
            $day = rand(1, $daysInMonth);
            $movementDate = Carbon::create($year, $month, $day);

            // Random amount (in cents)
            $amount = MoneyAction::toInteger(rand(100, 10000) + (rand(0, 99) / 100)); // 1.00 to 10000.99

            // Calculate VAT for income movements
            $vatAmount = 0;
            if ($type === 'income' && $vatProfile) {
                $vatAmount = MoneyAction::calculateVat($amount, (float) $vatProfile->percentage);
            }

            $totalAmount = $amount + $vatAmount;

            // Random supplier for expense movements
            $supplierId = null;
            if ($type === 'expense' && $suppliers->isNotEmpty() && rand(0, 1)) {
                $supplierId = $suppliers->random()->id;
            }

            // Random crew member for expense movements (optional)
            $crewMemberId = null;
            if ($type === 'expense' && rand(0, 3) === 0) {
                $crewMembers = User::where('vessel_id', $vessel->id)
                    ->whereNotNull('position_id')
                    ->get();
                if ($crewMembers->isNotEmpty()) {
                    $crewMemberId = $crewMembers->random()->id;
                }
            }

            // Sometimes include quantity and amount_per_unit
            $quantity = null;
            $amountPerUnit = null;
            if (rand(0, 2) === 0) {
                $quantity = rand(1, 100);
                $amountPerUnit = (int) ($amount / $quantity);
            }

            // Create movement
            try {
                $movement = Movimentation::create([
                    'vessel_id' => $vessel->id,
                    'category_id' => $category->id,
                    'supplier_id' => $supplierId,
                    'crew_member_id' => $crewMemberId,
                    'type' => $type,
                    'amount' => $amount,
                    'amount_per_unit' => $amountPerUnit,
                    'quantity' => $quantity,
                    'currency' => 'AOA',
                    'house_of_zeros' => 2,
                    'vat_profile_id' => $vatProfile ? $vatProfile->id : null,
                    'vat_amount' => $vatAmount,
                    'total_amount' => $totalAmount,
                    'transaction_date' => $movementDate->format('Y-m-d'),
                    'description' => $description . ' - ' . $movementDate->format('d/m/Y'),
                    'status' => $status,
                    'created_by' => $user->id,
                    'notes' => rand(0, 1) ? 'Test movement for ' . $movementDate->format('F Y') : null,
                ]);

                $created++;
            } catch (\Exception $e) {
                $this->command->warn("Failed to create movement: {$e->getMessage()}");
            }
        }

        return $created;
    }
}

