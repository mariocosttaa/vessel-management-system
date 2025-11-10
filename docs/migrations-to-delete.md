# Migrations to Delete After Consolidation

This document lists all migration files that should be deleted after consolidating changes into the create migrations.

## Add/Modify/Remove Migrations to Delete

### Users Table Related
- ✅ `2025_08_14_170933_add_two_factor_columns_to_users_table.php` - Consolidated into `0001_01_01_000000_create_users_table.php`
- ✅ `2025_10_12_033822_add_user_type_to_users_table.php` - Consolidated into `0001_01_01_000000_create_users_table.php`
- ✅ `2025_10_24_204912_transform_users_to_unified_crew_system.php` - Consolidated into `0001_01_01_000000_create_users_table.php`
- ✅ `2025_10_24_211325_remove_salary_fields_from_users_table.php` - Consolidated into `0001_01_01_000000_create_users_table.php`

### Vessels Table Related
- ✅ `2025_10_12_031428_add_country_currency_to_vessels_table.php` - Consolidated into `2025_10_11_185905_create_vessels_table.php`
- ✅ `2025_10_12_033217_fix_vessels_foreign_key_constraints.php` - Consolidated into `2025_10_11_185905_create_vessels_table.php`

### Crew Positions Table Related
- ✅ `2025_10_12_060303_add_vessel_id_to_crew_positions.php` - Consolidated into `2025_10_11_185908_create_crew_positions_table.php`
- ✅ `2025_11_09_232558_add_vessel_role_access_id_to_crew_positions_table.php` - Consolidated into `2025_10_11_185908_create_crew_positions_table.php`

### Suppliers Table Related
- ✅ `2025_10_12_055329_add_vessel_id_to_bank_accounts_and_suppliers.php` - Consolidated into `2025_10_11_185909_create_suppliers_table.php`
- ✅ `2025_11_07_000001_update_suppliers_table.php` - Consolidated into `2025_10_11_185909_create_suppliers_table.php`
- ✅ `2025_11_08_220704_add_description_to_suppliers_table.php` - Consolidated into `2025_10_11_185909_create_suppliers_table.php`

### Transactions Table Related
- ✅ `2025_11_08_194206_add_vat_profile_id_to_transactions_table.php` - Consolidated into `2025_10_11_185911_create_transactions_table.php`
- ✅ `2025_11_08_214253_remove_vat_rate_id_from_transactions_table.php` - Consolidated into `2025_10_11_185911_create_transactions_table.php`
- ✅ `2025_11_09_002731_remove_bank_account_id_from_transactions_table.php` - Consolidated into `2025_10_11_185911_create_transactions_table.php`
- ✅ `2025_11_09_132744_add_marea_id_to_transactions_table.php` - Consolidated into `2025_10_11_185911_create_transactions_table.php`
- ✅ `2025_11_09_202957_add_price_per_unit_and_quantity_to_transactions_table.php` - Consolidated into `2025_10_11_185911_create_transactions_table.php`
- ✅ `2025_11_09_220115_rename_price_per_unit_to_amount_per_unit_in_transactions_table.php` - Consolidated into `2025_10_11_185911_create_transactions_table.php`
- ✅ `2025_11_09_221431_change_quantity_to_integer_in_transactions_table.php` - Consolidated into `2025_10_11_185911_create_transactions_table.php`

### Recurring Transactions Table Related
- ✅ `2025_11_08_214501_add_vat_profile_id_to_recurring_transactions_and_remove_vat_rate_id.php` - Consolidated into `2025_10_11_185913_create_recurring_transactions_table.php`

### Mareas Table Related
- ✅ `2025_11_09_132828_add_distribution_profile_foreign_key_to_mareas_table.php` - Consolidated into `2025_11_09_132745_create_mareas_table.php`
- ✅ `2025_11_09_174118_add_calculation_fields_to_mareas_table.php` - Consolidated into `2025_11_09_132745_create_mareas_table.php`
- ✅ `2025_11_09_200437_remove_deleted_at_from_mareas_table.php` - Consolidated into `2025_11_09_132745_create_mareas_table.php` (deleted_at is kept)
- ✅ `2025_11_09_201225_add_deleted_at_back_to_mareas_table.php` - Consolidated into `2025_11_09_132745_create_mareas_table.php` (deleted_at is kept)

### Marea Quantity Return Table Related
- ✅ `2025_11_09_161542_remove_price_fields_from_marea_quantity_return_table.php` - Consolidated into `2025_11_09_132746_create_marea_quantity_return_table.php`

### Vessel Settings Table Related
- ✅ `2025_11_09_194839_add_starting_marea_number_to_vessel_settings_table.php` - Consolidated into `2025_11_08_201258_create_vessel_settings_table.php`

## Data Migrations (Review Before Deleting)

These migrations contain data transformations. Review if they're still needed:

- `2025_10_12_032245_migrate_user_vessels_to_vessel_users.php` - Data migration, may be safe to delete if data already migrated
- `2025_10_24_204932_migrate_crew_members_to_users_table.php` - Data migration, may be safe to delete if data already migrated
- `2025_11_08_224251_update_existing_transactions_currency_to_vessel_default.php` - Data migration, may be safe to delete if data already migrated

## Table Drop Migrations (Review)

- `2025_10_24_205953_drop_crew_members_table.php` - Table was dropped, migration can be deleted
- `2025_11_08_214254_remove_vat_rates_table.php` - Table was dropped, migration can be deleted
- `2025_11_09_002732_drop_bank_accounts_table.php` - Table was dropped, migration can be deleted

## Check Migrations (Review)

- `2025_10_12_060802_add_vessel_id_to_roles_or_remove_legacy_roles.php` - Check migration, review if roles table is still needed
- `2025_11_08_214255_check_and_remove_roles_table_if_unused.php` - Check migration, review if roles table is still needed

## Summary

**Total migrations to delete: ~30 files**

After consolidation, you should have:
- One `create_*_table.php` migration per table
- Data migrations (if still relevant)
- System migrations (cache, jobs, sessions, password_reset_tokens)

## Deletion Command

After verifying all consolidations are correct, you can delete these files. **Make sure to backup first!**

```bash
# List files to delete (review first!)
# Then delete them one by one or in batches
```

**IMPORTANT**: Only delete these files after:
1. All create migrations have been consolidated
2. Database has been tested with fresh migrations
3. All tests pass
4. You have a backup of the migration files

