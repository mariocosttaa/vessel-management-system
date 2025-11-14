<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;

class MovimentationCategory extends Model
{
    protected $table = 'transaction_categories';

    protected $fillable = [
        'name',
        'type',
        'parent_id',
        'description',
        'color',
        'is_system',
        'vessel_id',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MovimentationCategory::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(MovimentationCategory::class, 'parent_id');
    }

    /**
     * Get the transactions for the category.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Movimentation::class, 'category_id');
    }

    /**
     * Get the vessel that owns this category.
     */
    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Scope a query to only include income categories.
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    /**
     * Scope a query to only include expense categories.
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    /**
     * Scope a query to only include system categories.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope a query to only include custom categories.
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope a query to include categories for a specific vessel (or system categories).
     * System categories (vessel_id = null) are available to all vessels.
     * Custom categories (vessel_id = specific vessel) are only available to that vessel.
     */
    public function scopeForVessel($query, $vesselId)
    {
        return $query->where(function ($q) use ($vesselId) {
            $q->whereNull('vessel_id')         // System categories (available to all)
                ->orWhere('vessel_id', $vesselId); // Vessel-specific categories
        });
    }

    /**
     * Get the translated name of the category.
     * Falls back to original name if translation is missing.
     *
     * @return string
     */
    public function getTranslatedNameAttribute(): string
    {
        // Get user's language preference if available
        $locale = null;
        if (auth()->check() && auth()->user()->language) {
            $locale = auth()->user()->language;
        } else {
            $locale = App::getLocale();
        }

        // Get translation
        $originalLocale = App::getLocale();
        if ($locale) {
            App::setLocale($locale);
        }

        $translated = trans("categories.{$this->name}", [], $locale);

        // Restore original locale
        App::setLocale($originalLocale);

        // If translation not found (returns the full key), return original name
        if ($translated === "categories.{$this->name}") {
            return $this->name;
        }

        return $translated;
    }
}
