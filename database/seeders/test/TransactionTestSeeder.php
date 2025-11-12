<?php

namespace Database\Seeders\Test;

use App\Models\Transaction;
use App\Models\Vessel;
use App\Models\TransactionCategory;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VatProfile;
use App\Models\VesselSetting;
use App\Actions\MoneyAction;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('💰 Creating test transactions with historical data...');

        // Get available data
        $vessels = Vessel::where('status', 'active')->get();
        if ($vessels->isEmpty()) {
            $this->command->warn('No active vessels found. Please create vessels first.');
            return;
        }

        $categories = TransactionCategory::all();
        if ($categories->isEmpty()) {
            $this->command->warn('No transaction categories found. Please run TransactionCategorySeeder first.');
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

        $transactionTypes = ['income', 'expense'];
        $statuses = ['completed', 'pending', 'cancelled'];

        // Transaction descriptions
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

        // Create transactions for current month
        $this->command->info('Creating transactions for current month...');
        $createdCount += $this->createTransactionsForMonth(
            $now->year,
            $now->month,
            $vessels,
            $incomeCategories,
            $expenseCategories,
            $suppliers,
            $users,
            $vatProfiles,
            $defaultVatProfile,
            $transactionTypes,
            $statuses,
            $incomeDescriptions,
            $expenseDescriptions,
            15 // 15 transactions for current month
        );

        // Create transactions for previous months (last 4 months)
        for ($i = 1; $i <= 4; $i++) {
            $date = $now->copy()->subMonths($i);
            $this->command->info("Creating transactions for {$date->format('F Y')}...");
            $createdCount += $this->createTransactionsForMonth(
                $date->year,
                $date->month,
                $vessels,
                $incomeCategories,
                $expenseCategories,
                $suppliers,
                $users,
                $vatProfiles,
                $defaultVatProfile,
                $transactionTypes,
                $statuses,
                $incomeDescriptions,
                $expenseDescriptions,
                rand(10, 20) // Random number of transactions per month
            );
        }

        // Create transactions for previous years (last 2 years)
        for ($year = $now->year - 1; $year >= $now->year - 2; $year--) {
            // Create transactions for 3 random months in each year
            $months = collect(range(1, 12))->shuffle()->take(3);
            foreach ($months as $month) {
                $date = Carbon::create($year, $month, 1);
                $this->command->info("Creating transactions for {$date->format('F Y')}...");
                $createdCount += $this->createTransactionsForMonth(
                    $year,
                    $month,
                    $vessels,
                    $incomeCategories,
                    $expenseCategories,
                    $suppliers,
                    $users,
                    $vatProfiles,
                    $defaultVatProfile,
                    $transactionTypes,
                    $statuses,
                    $incomeDescriptions,
                    $expenseDescriptions,
                    rand(8, 15) // Random number of transactions per month
                );
            }
        }

        $this->command->info("✅ Created {$createdCount} test transactions successfully!");
        $this->command->info('Transaction history is now available for testing.');
    }

    /**
     * Create transactions for a specific month and year.
     */
    private function createTransactionsForMonth(
        int $year,
        int $month,
        $vessels,
        $incomeCategories,
        $expenseCategories,
        $suppliers,
        $users,
        $vatProfiles,
        $defaultVatProfile,
        array $transactionTypes,
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
            $type = $transactionTypes[array_rand($transactionTypes)];
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
            $transactionDate = Carbon::create($year, $month, $day);

            // Random amount (in cents)
            $amount = MoneyAction::toInteger(rand(100, 10000) + (rand(0, 99) / 100)); // 1.00 to 10000.99

            // Calculate VAT for income transactions
            $vatAmount = 0;
            if ($type === 'income' && $vatProfile) {
                $vatAmount = MoneyAction::calculateVat($amount, (float) $vatProfile->percentage);
            }

            $totalAmount = $amount + $vatAmount;

            // Random supplier for expense transactions
            $supplierId = null;
            if ($type === 'expense' && $suppliers->isNotEmpty() && rand(0, 1)) {
                $supplierId = $suppliers->random()->id;
            }

            // Random crew member for expense transactions (optional)
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

            // Create transaction
            try {
                $transaction = Transaction::create([
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
                    'transaction_date' => $transactionDate->format('Y-m-d'),
                    'description' => $description . ' - ' . $transactionDate->format('d/m/Y'),
                    'status' => $status,
                    'created_by' => $user->id,
                    'notes' => rand(0, 1) ? 'Test transaction for ' . $transactionDate->format('F Y') : null,
                ]);

                $created++;
            } catch (\Exception $e) {
                $this->command->warn("Failed to create transaction: {$e->getMessage()}");
            }
        }

        return $created;
    }
}

