<?php

namespace App\Models;

use App\Actions\MoneyAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Models\VesselSetting;

class Marea extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'marea_number',
        'vessel_id',
        'name',
        'description',
        'status',
        'estimated_departure_date',
        'estimated_return_date',
        'actual_departure_date',
        'actual_return_date',
        'closed_at',
        'distribution_profile_id',
        'use_calculation',
        'currency',
        'house_of_zeros',
        'created_by',
    ];

    protected $casts = [
        'estimated_departure_date' => 'date',
        'estimated_return_date' => 'date',
        'actual_departure_date' => 'date',
        'actual_return_date' => 'date',
        'closed_at' => 'datetime',
        'use_calculation' => 'boolean',
        'house_of_zeros' => 'integer',
    ];

    /**
     * Get the vessel that owns the marea.
     */
    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Get the distribution profile for the marea.
     */
    public function distributionProfile(): BelongsTo
    {
        return $this->belongsTo(MareaDistributionProfile::class, 'distribution_profile_id');
    }

    /**
     * Get the user that created the marea.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the transactions for the marea.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the crew members for the marea.
     */
    public function crew(): HasMany
    {
        return $this->hasMany(MareaCrew::class);
    }

    /**
     * Get the users (crew members) for the marea.
     */
    public function crewMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'marea_crew', 'marea_id', 'user_id')
            ->withPivot('notes')
            ->withTimestamps();
    }

    /**
     * Get the quantity returns for the marea.
     */
    public function quantityReturns(): HasMany
    {
        return $this->hasMany(MareaQuantityReturn::class);
    }

    /**
     * Get the distribution items for the marea (overrides).
     */
    public function distributionItems(): HasMany
    {
        return $this->hasMany(MareaDistributionItem::class)->orderBy('order_index');
    }

    /**
     * Scope a query to only include preparing mareas.
     */
    public function scopePreparing($query)
    {
        return $query->where('status', 'preparing');
    }

    /**
     * Scope a query to only include mareas at sea.
     */
    public function scopeAtSea($query)
    {
        return $query->where('status', 'at_sea');
    }

    /**
     * Scope a query to only include returned mareas.
     */
    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    /**
     * Scope a query to only include closed mareas.
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /**
     * Scope a query to only include cancelled mareas.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope a query to only include active mareas (preparing, at_sea, returned).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['preparing', 'at_sea', 'returned']);
    }

    /**
     * Scope a query to only include mareas for a specific vessel.
     */
    public function scopeForVessel($query, $vesselId)
    {
        return $query->where('vessel_id', $vesselId);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($marea) {
            if (!$marea->marea_number && $marea->vessel_id) {
                $marea->marea_number = self::generateMareaNumber($marea->vessel_id);
            }
        });
    }

    /**
     * Generate marea number based on vessel's starting number.
     */
    private static function generateMareaNumber(int $vesselId): string
    {
        // Get vessel settings to find starting marea number
        $vesselSetting = VesselSetting::getForVessel($vesselId);
        $startingNumber = $vesselSetting->starting_marea_number ?? 1;

        // Find the last marea for this vessel
        $lastMarea = self::where('vessel_id', $vesselId)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastMarea) {
            // Extract number from last marea (format: MARE2025000001)
            // Get the numeric part (last 6 digits)
            $lastNumber = (int) substr($lastMarea->marea_number, -6);
            $nextNumber = $lastNumber + 1;
        } else {
            // This is the first marea for this vessel, use starting number
            $nextNumber = $startingNumber;
        }

        $year = date('Y');
        return sprintf('MARE%s%06d', $year, $nextNumber);
    }

    /**
     * Get the next marea number for a vessel (for display purposes).
     */
    public static function getNextMareaNumber(int $vesselId): string
    {
        return self::generateMareaNumber($vesselId);
    }

    /**
     * Mark marea as at sea.
     */
    public function markAsAtSea($date = null): void
    {
        if ($this->status !== 'preparing') {
            throw new \Exception('Marea must be in preparing status to mark as at sea.');
        }

        $this->update([
            'status' => 'at_sea',
            'actual_departure_date' => $date ?? Carbon::now()->toDateString(),
        ]);
    }

    /**
     * Mark marea as returned.
     */
    public function markAsReturned($date = null): void
    {
        if ($this->status !== 'at_sea') {
            throw new \Exception('Marea must be at sea to mark as returned.');
        }

        $this->update([
            'status' => 'returned',
            'actual_return_date' => $date ?? Carbon::now()->toDateString(),
        ]);
    }

    /**
     * Close the marea.
     */
    public function close(): void
    {
        if ($this->status === 'closed') {
            throw new \Exception('Marea is already closed.');
        }

        if ($this->status === 'cancelled') {
            throw new \Exception('Cannot close a cancelled marea.');
        }

        $this->update([
            'status' => 'closed',
            'closed_at' => Carbon::now(),
        ]);
    }

    /**
     * Cancel the marea.
     */
    public function cancel(): void
    {
        if ($this->status === 'closed') {
            throw new \Exception('Cannot cancel a closed marea.');
        }

        if ($this->status === 'cancelled') {
            throw new \Exception('Marea is already cancelled.');
        }

        $this->update([
            'status' => 'cancelled',
        ]);
    }

    /**
     * Calculate total income from transactions.
     */
    public function getTotalIncomeAttribute(): int
    {
        return (int) ($this->transactions()
            ->where('type', 'income')
            ->sum('total_amount') ?? 0);
    }

    /**
     * Calculate total expenses from transactions.
     */
    public function getTotalExpensesAttribute(): int
    {
        return (int) ($this->transactions()
            ->where('type', 'expense')
            ->sum('total_amount') ?? 0);
    }

    /**
     * Calculate net result (income - expenses).
     */
    public function getNetResultAttribute(): int
    {
        return $this->total_income - $this->total_expenses;
    }

    /**
     * Get currency for this marea.
     */
    public function getCurrency(): ?string
    {
        if ($this->currency) {
            return strtoupper($this->currency);
        }

        // Fallback to vessel currency
        if ($this->vessel && $this->vessel->currency_code) {
            return strtoupper($this->vessel->currency_code);
        }

        return 'EUR'; // Final fallback
    }

    /**
     * Get house of zeros (decimal places) for this marea.
     */
    public function getHouseOfZeros(): int
    {
        return $this->house_of_zeros ?? 2;
    }

    /**
     * Get formatted total income attribute.
     */
    public function getFormattedTotalIncomeAttribute(): string
    {
        return MoneyAction::format(
            $this->total_income,
            $this->getHouseOfZeros(),
            $this->getCurrency(),
            true
        );
    }

    /**
     * Get formatted total expenses attribute.
     */
    public function getFormattedTotalExpensesAttribute(): string
    {
        return MoneyAction::format(
            $this->total_expenses,
            $this->getHouseOfZeros(),
            $this->getCurrency(),
            true
        );
    }

    /**
     * Get formatted net result attribute.
     */
    public function getFormattedNetResultAttribute(): string
    {
        return MoneyAction::format(
            $this->net_result,
            $this->getHouseOfZeros(),
            $this->getCurrency(),
            true
        );
    }

    /**
     * Calculate distribution result using the profile or marea-specific overrides.
     */
    public function calculateDistribution(): array
    {
        // If calculation is disabled, return basic values
        if (!$this->use_calculation) {
            return [
                'total_income' => $this->total_income,
                'total_expenses' => $this->total_expenses,
                'net_result' => $this->net_result,
                'final_result' => $this->net_result,
                'items' => [],
                'uses_overrides' => false,
            ];
        }

        // Use marea-specific items if they exist, otherwise use profile items
        $hasOverrides = $this->distributionItems()->exists();
        $items = $hasOverrides
            ? $this->distributionItems()->orderBy('order_index')->get()
            : ($this->distributionProfile ? $this->distributionProfile->items()->orderBy('order_index')->get() : collect());

        if ($items->isEmpty()) {
            return [
                'total_income' => $this->total_income,
                'total_expenses' => $this->total_expenses,
                'net_result' => $this->net_result,
                'final_result' => $this->net_result,
                'items' => [],
                'uses_overrides' => false,
            ];
        }

        $results = [];
        $totalIncome = $this->total_income;
        $totalExpenses = $this->total_expenses;

        foreach ($items as $item) {
            $value = $this->calculateItemValue($item, $totalIncome, $totalExpenses, $results);
            $result = $this->applyOperation($item, $value, $results);

            // Store result with item info
            $itemId = $hasOverrides ? $item->id : $item->id;
            $results[$itemId] = [
                'item' => $item,
                'value' => (int) round($result), // Ensure integer for money
                'formatted_value' => MoneyAction::format(
                    (int) round($result),
                    $this->getHouseOfZeros(),
                    $this->getCurrency(),
                    true
                ),
            ];
        }

        $finalResult = !empty($results) ? (int) round(end($results)['value']) : $this->net_result;

        return [
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_result' => $this->net_result,
            'final_result' => $finalResult,
            'formatted_final_result' => MoneyAction::format(
                $finalResult,
                $this->getHouseOfZeros(),
                $this->getCurrency(),
                true
            ),
            'items' => $results,
            'uses_overrides' => $hasOverrides,
        ];
    }

    /**
     * Calculate value for a distribution item.
     */
    private function calculateItemValue($item, $totalIncome, $totalExpenses, $results): float
    {
        switch ($item->value_type) {
            case 'base_total_income':
                return (float) $totalIncome;
            case 'base_total_expense':
                return (float) $totalExpenses;
            case 'fixed_amount':
                // value_amount is stored as decimal (e.g., 100.50 for 100.50 EUR)
                // Convert to cents by multiplying by 100
                return (float) ($item->value_amount ? round((float) $item->value_amount * 100) : 0);
            case 'percentage_of_income':
                return (float) $totalIncome * ((float) $item->value_amount / 100);
            case 'percentage_of_expense':
                return (float) $totalExpenses * ((float) $item->value_amount / 100);
            case 'reference_item':
                if ($item->reference_item_id && isset($results[$item->reference_item_id])) {
                    return (float) $results[$item->reference_item_id]['value'];
                }
                return 0.0;
            default:
                return 0.0;
        }
    }

    /**
     * Apply operation to a value.
     */
    private function applyOperation($item, $value, $results): float
    {
        if ($item->operation === 'set') {
            return (float) $value;
        }

        $operand = 0.0;
        if ($item->reference_operation_item_id && isset($results[$item->reference_operation_item_id])) {
            $operand = (float) $results[$item->reference_operation_item_id]['value'];
        } elseif (!empty($results)) {
            // Use last result if no reference specified
            $lastResult = end($results);
            $operand = (float) $lastResult['value'];
        }

        switch ($item->operation) {
            case 'add':
                return $operand + (float) $value;
            case 'subtract':
                return $operand - (float) $value;
            case 'multiply':
                return $operand * (float) $value;
            case 'divide':
                return (float) $value != 0 ? $operand / (float) $value : 0.0;
            default:
                return (float) $value;
        }
    }
}
