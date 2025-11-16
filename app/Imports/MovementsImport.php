<?php
namespace App\Imports;

use App\Actions\MoneyAction;
use App\Models\Maintenance;
use App\Models\Marea;
use App\Models\Movimentation;
use App\Models\MovimentationCategory;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VatProfile;
use App\Models\VesselSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MovementsImport implements ToCollection, WithHeadingRow
{
    protected int $vesselId;
    protected ?int $userId;
    protected bool $skipDuplicates;
    protected bool $ignoreTransactionNumbers;
    protected ?int $mareaId;
    protected ?int $maintenanceId;
    protected array $errors     = [];
    protected array $skipped    = [];
    protected int $successCount = 0;
    protected int $errorCount   = 0;
    protected int $skippedCount = 0;

    public function __construct(int $vesselId, ?int $userId = null, bool $skipDuplicates = true, bool $ignoreTransactionNumbers = false, ?int $mareaId = null, ?int $maintenanceId = null)
    {
        $this->vesselId                 = $vesselId;
        $this->userId                   = $userId;
        $this->skipDuplicates           = $skipDuplicates;
        $this->ignoreTransactionNumbers = $ignoreTransactionNumbers;
        $this->mareaId                  = $mareaId;
        $this->maintenanceId            = $maintenanceId;
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            throw new \Exception('The Excel file is empty or has no data rows.');
        }

        // Log first row for debugging
        if ($rows->isNotEmpty()) {
            Log::info('First row keys:', ['keys' => $rows->first()->keys()->toArray()]);
        }

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because heading row is 1 and array is 0-indexed

                // Skip completely empty rows
                if ($row->filter(function ($value) {
                    return ! is_null($value) && $value !== '';
                })->isEmpty()) {
                    continue;
                }

                try {
                    $this->importRow($row, $rowNumber);
                    $this->successCount++;
                } catch (\Exception $e) {
                    $errorMessage = $e->getMessage();

                    // Check if this is a skipped row (duplicate transaction number)
                    if (strpos($errorMessage, 'already exists (skipping)') !== false) {
                        $this->skippedCount++;
                        $this->skipped[] = [
                            'row'    => $rowNumber,
                            'reason' => $errorMessage,
                        ];

                        // Log first few skipped rows
                        if ($this->skippedCount <= 3) {
                            Log::info("Import skipped row {$rowNumber}: {$errorMessage}");
                        }
                    } else {
                        // This is a real error
                        $this->errorCount++;
                        $this->errors[] = [
                            'row'   => $rowNumber,
                            'error' => $errorMessage,
                        ];

                        // Log first few errors for debugging
                        if ($this->errorCount <= 3) {
                            Log::warning("Import error on row {$rowNumber}: {$errorMessage}", [
                                'row_data' => $row->toArray(),
                            ]);
                        }
                    }
                    // Continue processing other rows even if one fails
                }
            }

            // Only fail if there are actual errors (not just skipped duplicates)
            if ($this->successCount === 0 && $this->errorCount > 0) {
                DB::rollBack();
                throw new \Exception('No transactions were imported. All rows had errors. Check the file format.');
            }

            // If all rows were skipped (duplicates), that's okay - just inform the user
            if ($this->successCount === 0 && $this->skippedCount > 0 && $this->errorCount === 0) {
                // Don't rollback - this is a successful import (just nothing new to import)
                // The transaction will commit successfully
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Import a single row
     */
    protected function importRow(Collection $row, int $rowNumber): void
    {
        // Normalize column names (handle different formats)
        $data = $this->normalizeRow($row);

        // Log normalized data for first row to debug
        if ($rowNumber === 2) {
            Log::info('Normalized first row data:', ['data' => $data]);
            Log::info('Raw row data:', ['row' => $row->toArray()]);
        }

        // Validate required fields: category_name, amount, description
        if (empty($data['category_name'])) {
            throw new \Exception('Category name is required');
        }

        // Try to get amount from various sources: amount_cents (preferred), amount, or amount_formatted
        // Priority: amount_cents (integer) > amount (integer) > amount_formatted (string)
        $amountValue = $data['amount_cents'] ?? $data['amount'] ?? $data['amount_formatted'] ?? null;
        if ($amountValue === null || $amountValue === '') {
            throw new \Exception('Amount is required');
        }

        // Description is required, but if empty, use category name as fallback
        $description = trim($data['description'] ?? '');
        if (empty($description)) {
            // Try to find description in raw row if not found in normalized data
            // Sometimes description might be in a different column position
            $rawDescription = null;
            foreach ($row as $key => $value) {
                if (is_string($key)) {
                    $normalizedKey = strtolower(str_replace([' ', '_', '-'], '', $key));
                    if (strpos($normalizedKey, 'desc') !== false && ! empty(trim($value ?? ''))) {
                        $rawDescription = trim($value);
                        break;
                    }
                }
            }

            if ($rawDescription) {
                $description = $rawDescription;
            } else {
                // Use category name as fallback description
                $description = $data['category_name'];
                Log::info("Row {$rowNumber}: Description was empty, using category name as fallback: {$description}");
            }
        }

        // Look up category by name (for this vessel)
        $categoryId = $this->findCategoryByName($data['category_name'], 'Category');

        // Get transaction type from data or category (needed before VAT calculation)
        $type = $data['type'] ?? null;
        if (empty($type)) {
            // Fallback to category type
            $category = MovimentationCategory::find($categoryId);
            $type     = $category?->type ?? 'expense';
        } else {
            // Validate type
            $type = strtolower(trim($type));
            if (! in_array($type, ['income', 'expense', 'transfer'])) {
                throw new \Exception("Invalid transaction type: {$type}. Must be 'income', 'expense', or 'transfer'");
            }
        }

        // Get currency from vessel settings, fallback to transaction currency or EUR
        $currency = $data['currency'] ?? null;
        if (empty($currency)) {
            // Get currency from vessel settings
            $vesselSetting = VesselSetting::where('vessel_id', $this->vesselId)->first();
            $currency      = $vesselSetting?->currency_code ?? 'EUR';
        }

                           // Get house_of_zeros from currency or default to 2
        $houseOfZeros = 2; // Default
        if ($currency) {
            // Try to get decimals from currency table
            $currencyModel = \App\Models\Currency::where('code', $currency)->first();
            if ($currencyModel && isset($currencyModel->decimal_separator)) {
                $houseOfZeros = $currencyModel->decimal_separator;
            }
        }

        // Parse amount (should be integer in cents)
        // Priority: amount_cents (integer) > amount (integer) > amount_formatted (string)
        $amount = $this->parseAmount($amountValue, 'Amount');

        // Get quantity if provided
        $quantity = ! empty($data['quantity']) ? (float) $data['quantity'] : null;

        // Get amount_includes_vat boolean (only relevant for income transactions)
        $amountIncludesVat = false;
        if ($type === 'income') {
            $amountIncludesVatValue = $data['amount_includes_vat'] ?? $data['amount includes vat'] ?? $data['valor já inclui iva'] ?? null;
            if ($amountIncludesVatValue !== null) {
                // Handle Yes/No, Sim/Não, Sí/No, Oui/Non, true/false, 1/0
                $normalizedValue = is_string($amountIncludesVatValue)
                    ? strtolower(trim($amountIncludesVatValue))
                    : $amountIncludesVatValue;

                $amountIncludesVat = in_array($normalizedValue, [
                    'yes', 'sim', 'sí', 'oui', 'true', '1', 'on', 'enabled', 'checked',
                ], true);
            }
        }

        // Get VAT profile for income transactions
        $vatProfileId = null;
        $vatRate      = 0;
        if ($type === 'income') {
            $vesselSetting = VesselSetting::where('vessel_id', $this->vesselId)->first();
            $vatProfileId  = $vesselSetting?->vat_profile_id;

            if ($vatProfileId) {
                $vatProfile = VatProfile::find($vatProfileId);
                if ($vatProfile) {
                    $vatRate = (float) $vatProfile->percentage;
                }
            }
        }

        // Calculate VAT and total amount based on type and amount_includes_vat
        $vatAmount   = 0;
        $totalAmount = null;

        if ($type === 'income' && $vatRate > 0) {
            // For income transactions with VAT
            if ($amountIncludesVat) {
                // Amount includes VAT - separate it
                $calculation = MoneyAction::calculateFromTotalIncludingVat($amount, $vatRate, $houseOfZeros);
                $amount      = $calculation['base']; // Store base amount
                $vatAmount   = $calculation['vat'];  // Store VAT amount
                $totalAmount = $amount + $vatAmount; // Should equal original amount
            } else {
                // Amount excludes VAT - calculate VAT on top
                $vatAmount   = MoneyAction::calculateVat($amount, $vatRate, $houseOfZeros);
                $totalAmount = $amount + $vatAmount;
            }
        } else {
            // For expense/transfer or income without VAT profile
            // Get VAT amount if provided (in cents)
            if (! empty($data['vat_amount_cents']) || ! empty($data['vat_amount'])) {
                $vatAmountValue = $data['vat_amount_cents'] ?? $data['vat_amount'] ?? null;
                $vatAmount      = $this->parseAmount($vatAmountValue, 'VAT Amount', true) ?? 0;
            } elseif (! empty($data['vat_amount_formatted'])) {
                $vatAmount = $this->parseAmount($data['vat_amount_formatted'], 'VAT Amount', true) ?? 0;
            } else {
                $vatAmount = 0; // Default to 0 if not provided
            }

            // Get total amount if provided, otherwise calculate from amount + VAT
            if (! empty($data['total_amount_cents']) || ! empty($data['total_amount'])) {
                $totalAmountValue = $data['total_amount_cents'] ?? $data['total_amount'] ?? null;
                $totalAmount      = $this->parseAmount($totalAmountValue, 'Total Amount');
            } elseif (! empty($data['total_amount_formatted'])) {
                $totalAmount = $this->parseAmount($data['total_amount_formatted'], 'Total Amount');
            }

            // If total amount not provided, calculate from amount + VAT
            if ($totalAmount === null) {
                $totalAmount = $amount + $vatAmount;
            }
        }

        // Description already processed above
        $notes = ! empty($data['notes']) ? trim($data['notes']) : null;

        // Handle transaction number (optional - if provided, will update existing)
        // If ignoreTransactionNumbers is true, always set to null to create new transactions
        $transactionNumber = null;
        if (! $this->ignoreTransactionNumbers && ! empty($data['transaction_number'])) {
            $transactionNumber = trim($data['transaction_number']);
        }

        // Check if transaction number already exists for this vessel (only if not ignoring transaction numbers)
        $existingTransaction = null;
        if ($transactionNumber && ! $this->ignoreTransactionNumbers) {
            $existingTransaction = Movimentation::where('vessel_id', $this->vesselId)
                ->where('transaction_number', $transactionNumber)
                ->first();

            if ($existingTransaction && $this->skipDuplicates) {
                // Skip duplicate transaction numbers when skipDuplicates is enabled
                throw new \Exception("Transaction number already exists (skipping): {$transactionNumber}");
            }
        }

        // Use today's date as default transaction date
        $transactionDate  = Carbon::today();
        $transactionMonth = $transactionDate->month;
        $transactionYear  = $transactionDate->year;

        // Prepare transaction data
        $transactionData = [
            'vessel_id'         => $this->vesselId,
            'marea_id'          => $this->mareaId,       // Link to marea if provided
            'maintenance_id'    => $this->maintenanceId, // Link to maintenance if provided
            'category_id'       => $categoryId,
            'type'              => $type,
            'amount'            => $amount,
            'amount_per_unit'   => $quantity ? ($amount / $quantity) : null,
            'quantity'          => $quantity,
            'currency'          => $currency,
            'house_of_zeros'    => $houseOfZeros,
            'vat_profile_id'    => $vatProfileId, // Only set for income transactions
            'vat_amount'        => $vatAmount,
            'total_amount'      => $totalAmount,
            'transaction_date'  => $transactionDate,
            'transaction_month' => $transactionMonth,
            'transaction_year'  => $transactionYear,
            'description'       => $description,
            'notes'             => $notes,
            'is_recurring'      => false,
            'status'            => 'completed',
        ];

        // If transaction number is provided, include it
        if ($transactionNumber) {
            $transactionData['transaction_number'] = $transactionNumber;
        }

        // If transaction exists and skipDuplicates is false, update it; otherwise create new
        if ($existingTransaction && ! $this->skipDuplicates) {
            // Update existing transaction
            $existingTransaction->update($transactionData);
            $transaction = $existingTransaction;
        } else {
            // Create new transaction (transaction_number will be auto-generated if not provided)
            $transactionData['created_by'] = $this->userId;
            $transaction                   = Movimentation::create($transactionData);
        }
    }

    /**
     * Normalize row data (handle different column name formats)
     * Simplified to only handle: category_name, amount, description, notes, transaction_number
     */
    protected function normalizeRow(Collection $row): array
    {
        $normalized = [];

        // Simplified mappings - only the fields we need
        // Accept amount in various formats: amount, amount_cents, amount_formatted
        // Also accept quantity and total_amount/total_amount_formatted
        // Other columns will be ignored (no error)
        $mappings = [
            'category_name'          => ['category_name', 'category name', 'Category Name', 'category', 'categoria'],
            'amount'                 => ['amount', 'Amount', 'amount_cents', 'amount (cents)', 'Amount (Cents)', 'valor', 'Valor'],
            'amount_formatted'       => ['amount_formatted', 'amount (formatted)', 'Amount (Formatted)'],
            'type'                   => ['type', 'Type', 'tipo', 'Tipo'],
            'vat_amount'             => ['vat_amount', 'vat_amount_cents', 'vat_amount (cents)', 'VAT Amount (Cents)', 'vat'],
            'vat_amount_formatted'   => ['vat_amount_formatted', 'vat_amount (formatted)', 'VAT Amount (Formatted)'],
            'total_amount'           => ['total_amount', 'total_amount_cents', 'total_amount (cents)', 'Total Amount (Cents)'],
            'total_amount_formatted' => ['total_amount_formatted', 'total_amount (formatted)', 'Total Amount (Formatted)'],
            'currency'               => ['currency', 'Currency', 'moeda', 'Moeda'],
            'amount_includes_vat'    => ['amount_includes_vat', 'amount includes vat', 'Amount Includes VAT', 'valor já inclui iva', 'Valor já inclui IVA'],
            'quantity'               => ['quantity', 'Quantity', 'quantidade', 'Quantidade'],
            'description'            => ['description', 'Description', 'descrição', 'Descrição'],
            'notes'                  => ['notes', 'Notes', 'notas', 'Notas'],
            'transaction_number'     => ['transaction_number', 'transaction number', 'Transaction Number', 'transaction', 'número', 'numero'],
        ];

        foreach ($mappings as $key => $variations) {
            foreach ($variations as $variation) {
                // Try exact match first (case-sensitive)
                if (isset($row[$variation]) && $row[$variation] !== null && $row[$variation] !== '') {
                    $normalized[$key] = $row[$variation];
                    break;
                }

                // Try case-insensitive match
                foreach ($row->keys() as $rowKey) {
                    // Skip numeric keys (these are usually empty columns)
                    if (is_numeric($rowKey)) {
                        continue;
                    }

                    // Normalize both keys for comparison (remove spaces, underscores, convert to lowercase)
                    $normalizedRowKey    = strtolower(str_replace([' ', '_', '-'], '', $rowKey));
                    $normalizedVariation = strtolower(str_replace([' ', '_', '-'], '', $variation));

                    if ($normalizedRowKey === $normalizedVariation) {
                        $value = $row[$rowKey];
                        // Only set if value is not null and not empty string
                        if ($value !== null && $value !== '') {
                            $normalized[$key] = $value;
                            break 2;
                        }
                    }
                }
            }
        }

        return $normalized;
    }

    /**
     * Validate row data
     */
    protected function validateRow(array $data, int $rowNumber): void
    {
        // Check that at least one amount field is provided
        $hasAmount = ! empty($data['amount_cents']) || ! empty($data['amount']) ||
        ! empty($data['amount_formatted']) || ! empty($data['amount_(formatted)']);

        $validator = Validator::make($data, [
            'type'             => 'required|in:income,expense,transfer',
            'transaction_date' => 'required', // Accept any format, parseDate will handle conversion
            'category_name'    => 'required',
            'status'           => 'nullable|in:pending,completed,cancelled',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            throw new \Exception("Row {$rowNumber} validation failed: " . implode(', ', $errors));
        }

        if (! $hasAmount) {
            throw new \Exception("Row {$rowNumber} validation failed: At least one amount field (amount_cents or amount) is required");
        }
    }

    /**
     * Find category by name (supports both actual name and translated name)
     */
    protected function findCategoryByName(?string $name, string $label): int
    {
        if (empty($name)) {
            throw new \Exception("{$label} name is required");
        }

        $normalizedName          = mb_strtolower(trim($name));
        $normalizedNameNoAccents = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalizedName);

        // Try exact match first (case-sensitive) - actual database name
        $category = MovimentationCategory::forVessel($this->vesselId)
            ->where('name', $name)
            ->first();

        // Try case-insensitive exact match on database name
        if (! $category) {
            $category = MovimentationCategory::forVessel($this->vesselId)
                ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])
                ->first();
        }

        // If not found, try matching against translated names
        // Load all categories and check their translated_name
        if (! $category) {
            $categories = MovimentationCategory::forVessel($this->vesselId)->get();
            foreach ($categories as $cat) {
                $translatedName          = mb_strtolower(trim($cat->translated_name));
                $translatedNameNoAccents = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $translatedName);

                // Check exact match
                if ($translatedName === $normalizedName || $translatedNameNoAccents === $normalizedNameNoAccents) {
                    $category = $cat;
                    break;
                }

                // Check partial match
                if (strpos($translatedName, $normalizedName) !== false ||
                    strpos($translatedNameNoAccents, $normalizedNameNoAccents) !== false) {
                    $category = $cat;
                    break;
                }
            }
        }

        // If still not found, try LIKE search on database name (case-insensitive)
        if (! $category) {
            $searchTerm = '%' . $normalizedName . '%';
            $category   = MovimentationCategory::forVessel($this->vesselId)
                ->whereRaw('LOWER(name) LIKE ?', [$searchTerm])
                ->first();
        }

        if (! $category) {
            // Get available categories for better error message
            $availableCategories = MovimentationCategory::forVessel($this->vesselId)
                ->pluck('name')
                ->take(10)
                ->implode(', ');
            throw new \Exception("{$label} not found: '{$name}'. Available categories: {$availableCategories}");
        }

        return $category->id;
    }

    /**
     * Find supplier by name
     */
    protected function findSupplierByName(?string $name, string $label, bool $nullable = false): ?int
    {
        if (empty($name)) {
            return $nullable ? null : throw new \Exception("{$label} name is required");
        }

        // Search by exact match first, then by LIKE for partial matches, filtered by vessel
        $supplier = Supplier::where('vessel_id', $this->vesselId)
            ->where(function ($query) use ($name) {
                $query->where('company_name', $name)
                    ->orWhere('company_name', 'like', "%{$name}%");
            })
            ->first();

        if (! $supplier) {
            if ($nullable) {
                return null;
            }
            throw new \Exception("{$label} not found: {$name}");
        }

        return $supplier->id;
    }

    /**
     * Find crew member by name or email
     */
    protected function findCrewMemberByNameOrEmail(?string $name, ?string $email, string $label, bool $nullable = false): ?int
    {
        if (empty($name) && empty($email)) {
            return $nullable ? null : throw new \Exception("{$label} name or email is required");
        }

        $query = User::query();

        if ($email) {
            $query->where('email', $email);
        } elseif ($name) {
            $query->where('name', 'like', "%{$name}%");
        }

        $user = $query->first();

        if (! $user) {
            if ($nullable) {
                return null;
            }
            throw new \Exception("{$label} not found: " . ($email ?: $name));
        }

        return $user->id;
    }

    /**
     * Find marea by number
     */
    protected function findMareaByNumber(?string $number, string $label, bool $nullable = false): ?int
    {
        if (empty($number)) {
            return $nullable ? null : throw new \Exception("{$label} number is required");
        }

        $marea = Marea::where('marea_number', $number)
            ->where('vessel_id', $this->vesselId)
            ->first();

        if (! $marea) {
            if ($nullable) {
                return null;
            }
            throw new \Exception("{$label} not found: {$number}");
        }

        return $marea->id;
    }

    /**
     * Find maintenance by number
     */
    protected function findMaintenanceByNumber(?string $number, string $label, bool $nullable = false): ?int
    {
        if (empty($number)) {
            return $nullable ? null : throw new \Exception("{$label} number is required");
        }

        $maintenance = Maintenance::where('maintenance_number', $number)
            ->where('vessel_id', $this->vesselId)
            ->first();

        if (! $maintenance) {
            if ($nullable) {
                return null;
            }
            throw new \Exception("{$label} not found: {$number}");
        }

        return $maintenance->id;
    }

    /**
     * Find user by name or email
     */
    protected function findUserByNameOrEmail(?string $name, ?string $email, string $label, bool $nullable = false): ?int
    {
        if (empty($name) && empty($email)) {
            return $nullable ? null : throw new \Exception("{$label} name or email is required");
        }

        $query = User::query();

        if ($email) {
            $query->where('email', $email);
        } elseif ($name) {
            $query->where('name', 'like', "%{$name}%");
        }

        $user = $query->first();

        if (! $user) {
            if ($nullable) {
                return null;
            }
            throw new \Exception("{$label} not found: " . ($email ?: $name));
        }

        return $user->id;
    }

    /**
     * Parse date from various formats
     */
    protected function parseDate($date, string $label): string
    {
        if (empty($date)) {
            throw new \Exception("{$label} is required");
        }

        try {
            // Handle Excel date serial numbers (e.g., 45234.0)
            if (is_numeric($date)) {
                // Excel epoch starts on 1900-01-01, but Excel incorrectly treats 1900 as a leap year
                // So we need to adjust: Excel serial 1 = 1900-01-01
                $excelEpoch = Carbon::create(1899, 12, 30);
                $date       = $excelEpoch->addDays((int) $date)->format('Y-m-d');
            }

            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            throw new \Exception("Invalid {$label} format: {$date}. Error: " . $e->getMessage());
        }
    }

    /**
     * Parse amount (can be in cents or formatted)
     */
    protected function parseAmount($value, string $label, bool $nullable = false): ?int
    {
        if ($value === null || $value === '') {
            return $nullable ? null : throw new \Exception("{$label} is required");
        }

        // If it's already an integer, assume it's in cents
        if (is_int($value)) {
            return $value;
        }

        // If it's a float, check if it represents cents (no decimal part or decimal part is 0)
        if (is_float($value)) {
            // If the decimal part is 0 or very small (floating point precision), treat as cents
            if (abs($value - round($value)) < 0.0001) {
                return (int) round($value);
            }
            // Otherwise, it might be a formatted amount that needs conversion
            // But typically Excel exports cents as whole numbers, so this shouldn't happen
            return (int) round($value);
        }

        // If it's a numeric string without decimal point, assume it's in cents
        if (is_string($value) && is_numeric($value) && strpos($value, '.') === false) {
            return (int) $value;
        }

        // If it's a numeric string with decimal point, check if it's already in cents format
        if (is_string($value) && is_numeric($value)) {
            $floatValue = (float) $value;
            // If the decimal part is 0 or very small, treat as cents
            if (abs($floatValue - round($floatValue)) < 0.0001) {
                return (int) round($floatValue);
            }
            // Otherwise, it might be a formatted amount
            // Try to parse as formatted money string
        }

        // Try to parse as formatted money string
        if (is_string($value)) {
            // Remove currency symbols and spaces
            $cleaned = preg_replace('/[^\d.,-]/', '', $value);
            // Replace comma with dot if it's the decimal separator
            $cleaned = str_replace(',', '.', $cleaned);
            // Parse as float and convert to cents
            $floatValue = (float) $cleaned;
            return (int) round($floatValue * 100);
        }

        if ($nullable) {
            return null;
        }

        throw new \Exception("Invalid {$label} format: {$value}");
    }

    /**
     * Get import results
     */
    public function getResults(): array
    {
        return [
            'success_count' => $this->successCount,
            'error_count'   => $this->errorCount,
            'skipped_count' => $this->skippedCount,
            'errors'        => $this->errors,
            'skipped'       => $this->skipped,
        ];
    }
}
