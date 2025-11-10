# Migration Patterns and Best Practices

## Overview

This document outlines the migration patterns and best practices for the Vessel Management System to prevent fragmented migrations and maintain a clean migration history.

## Core Principle: One Table = One Migration

**CRITICAL RULE**: Each table should have exactly ONE migration file that creates it with its complete, final schema. During development, if you need to change a table structure, **edit the existing create migration** instead of creating new "add" or "modify" migrations.

## Development vs Production

### Development Environment

Since the application is **not in production**, you can safely:

1. **Edit existing create migrations** directly when you need to change a table structure
2. **Delete and recreate** the database when needed
3. **Consolidate changes** into the create migration instead of creating new migrations

### Production Environment (Future)

Once the application is in production:

1. **NEVER edit existing migrations** that have already run
2. **Create new migrations** for schema changes
3. **Use proper migration naming** (add_column, modify_column, etc.)
4. **Test migrations** on staging first
5. **Create rollback strategies** for all migrations

## Migration Naming Conventions

### Create Migrations

Use the format: `YYYY_MM_DD_HHMMSS_create_[table_name]_table.php`

Example:
- `2025_10_11_185911_create_transactions_table.php`
- `2025_11_09_132745_create_mareas_table.php`

### Modification Migrations (Production Only)

If you must create a modification migration in production, use clear, descriptive names:

- `YYYY_MM_DD_HHMMSS_add_[column_name]_to_[table_name]_table.php`
- `YYYY_MM_DD_HHMMSS_modify_[column_name]_in_[table_name]_table.php`
- `YYYY_MM_DD_HHMMSS_remove_[column_name]_from_[table_name]_table.php`

**Note**: These should only be used in production. In development, edit the create migration instead.

## Migration Structure

### Standard Create Migration Template

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('table_name', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Foreign keys (if any)
            $table->foreignId('related_id')->nullable()->constrained('related_table')->onDelete('cascade');
            
            // Columns
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes(); // If needed
            
            // Indexes
            $table->index('status');
            $table->index(['related_id', 'status']);
            
            // Unique constraints
            $table->unique(['related_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
};
```

## Common Patterns

### Foreign Keys

Always define foreign keys in the create migration:

```php
// Correct: Define in create migration
$table->foreignId('vessel_id')->nullable()->constrained()->onDelete('set null');

// Wrong: Adding in separate migration (development only)
// Don't create: add_vessel_id_to_table.php
```

### Indexes

Define all indexes in the create migration:

```php
// Single column index
$table->index('status');

// Composite index
$table->index(['vessel_id', 'status'], 'idx_vessel_status');

// Unique index
$table->unique(['vessel_id', 'name']);
```

### Enums

Define enum values in the create migration with all possible values:

```php
// Include all enum values from the start
$table->enum('status', ['active', 'suspended', 'maintenance', 'inactive'])->default('active');
```

### Money Fields

Always use `bigInteger` for money fields (stored in cents):

```php
$table->bigInteger('amount')->comment('Amount in cents');
$table->string('currency', 3)->default('EUR');
$table->tinyInteger('house_of_zeros')->default(2);
```

## What NOT to Do (Development)

### ❌ Don't Create These in Development

1. **Add column migrations**
   ```php
   // DON'T: 2025_11_09_202957_add_price_per_unit_to_transactions_table.php
   // DO: Edit the create_transactions_table.php migration directly
   ```

2. **Modify column migrations**
   ```php
   // DON'T: 2025_11_09_221431_change_quantity_to_integer_in_transactions_table.php
   // DO: Edit the create_transactions_table.php migration directly
   ```

3. **Remove column migrations**
   ```php
   // DON'T: 2025_11_09_161542_remove_price_fields_from_marea_quantity_return_table.php
   // DO: Edit the create_marea_quantity_return_table.php migration directly
   ```

4. **Rename column migrations**
   ```php
   // DON'T: 2025_11_09_220115_rename_price_per_unit_to_amount_per_unit_in_transactions_table.php
   // DO: Edit the create_transactions_table.php migration directly
   ```

5. **Add foreign key migrations**
   ```php
   // DON'T: 2025_11_09_132828_add_distribution_profile_foreign_key_to_mareas_table.php
   // DO: Edit the create_mareas_table.php migration directly
   ```

## Workflow for Schema Changes (Development)

### Step 1: Identify the Table

Determine which table needs to be changed.

### Step 2: Edit the Create Migration

Open the `create_[table_name]_table.php` migration file and make your changes directly.

### Step 3: Reset the Database (if needed)

If the migration has already run, you may need to:

```bash
# Option 1: Fresh migration
php artisan migrate:fresh

# Option 2: Rollback and re-run
php artisan migrate:rollback --step=N
php artisan migrate
```

### Step 4: Verify

Check that the table structure matches your expectations:

```bash
php artisan migrate:status
```

## Migration Order and Dependencies

When creating new tables, ensure proper migration order based on foreign key dependencies:

1. **Independent tables first** (countries, currencies, roles)
2. **Core entities** (users, vessels)
3. **Dependent tables** (transactions, mareas, etc.)

### Dependency Chain Example

```
countries, currencies (no dependencies)
  ↓
users, roles
  ↓
vessels (depends on users)
  ↓
vessel_settings (depends on vessels, countries, currencies, vat_profiles)
  ↓
transactions (depends on vessels, categories, suppliers, users, vat_profiles, mareas)
```

## Data Migrations

For data migrations (moving data between tables, transforming data), create separate migration files with clear names:

```php
// Example: 2025_10_24_204932_migrate_crew_members_to_users_table.php
// This is acceptable because it's a data migration, not a schema change
```

**Note**: Data migrations are acceptable even in development, but schema changes should be consolidated into create migrations.

## Checklist Before Creating a Migration

Before creating a new migration, ask yourself:

- [ ] Is this a schema change (add/modify/remove column)?
  - **If YES**: Edit the existing create migration instead
- [ ] Is this a new table?
  - **If YES**: Create a new create migration
- [ ] Is this a data migration?
  - **If YES**: Create a data migration (acceptable)
- [ ] Am I in development or production?
  - **Development**: Edit create migrations directly
  - **Production**: Create new migration files

## Examples

### ✅ Good: Consolidated Create Migration

```php
// 2025_10_11_185911_create_transactions_table.php
Schema::create('transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('vessel_id')->nullable()->constrained()->onDelete('set null');
    $table->foreignId('marea_id')->nullable()->constrained('mareas')->onDelete('set null');
    $table->bigInteger('amount');
    $table->bigInteger('amount_per_unit')->nullable(); // Added directly
    $table->integer('quantity')->nullable(); // Added directly
    $table->foreignId('vat_profile_id')->nullable()->constrained('vat_profiles')->onDelete('set null');
    // ... all fields in one place
});
```

### ❌ Bad: Fragmented Migrations

```php
// 2025_10_11_185911_create_transactions_table.php
Schema::create('transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('vessel_id')->nullable()->constrained()->onDelete('set null');
    // Missing fields...
});

// 2025_11_09_132744_add_marea_id_to_transactions_table.php
Schema::table('transactions', function (Blueprint $table) {
    $table->foreignId('marea_id')->nullable()->after('vessel_id')->constrained('mareas')->onDelete('set null');
});

// 2025_11_09_202957_add_price_per_unit_and_quantity_to_transactions_table.php
Schema::table('transactions', function (Blueprint $table) {
    $table->bigInteger('price_per_unit')->nullable()->after('amount');
    $table->decimal('quantity', 10, 2)->nullable()->after('price_per_unit');
});
// ... and so on
```

## Summary

1. **One table = One create migration** with complete schema
2. **Edit create migrations** during development instead of creating new migrations
3. **Consolidate all changes** into the create migration
4. **Delete fragmented migrations** after consolidation
5. **Follow proper naming** conventions
6. **Respect dependencies** when creating new tables
7. **Use data migrations** only for data transformations, not schema changes

## Migration Cleanup

After consolidating migrations:

1. Delete all `add_*_to_*_table.php` migrations
2. Delete all `modify_*_in_*_table.php` migrations
3. Delete all `remove_*_from_*_table.php` migrations
4. Delete all `rename_*_in_*_table.php` migrations
5. Keep only `create_*_table.php` migrations
6. Keep data migrations if they're still relevant

## Resources

- [Laravel Migrations Documentation](https://laravel.com/docs/migrations)
- [Database Schema Reference](../database-schema.md)
- [Migration Consolidation Analysis](../migration-consolidation-analysis.md)

