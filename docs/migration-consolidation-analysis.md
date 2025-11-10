# Migration Consolidation Analysis

This document tracks the consolidation of all migrations into single "create" migrations per table.

## Tables to Consolidate

### 1. users
**Current Migrations:**
- `0001_01_01_000000_create_users_table.php` - Base table
- `2025_08_14_170933_add_two_factor_columns_to_users_table.php` - Two factor auth
- `2025_10_12_033822_add_user_type_to_users_table.php` - User type enum
- `2025_10_24_204912_transform_users_to_unified_crew_system.php` - Crew member fields
- `2025_10_24_211325_remove_salary_fields_from_users_table.php` - Remove salary fields

**Final Schema:**
- id, name, email, email_verified_at, password, remember_token
- two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at
- user_type (enum: paid_system, employee_of_vessel)
- vessel_id, position_id (crew member fields)
- phone, date_of_birth, hire_date, house_of_zeros
- status (enum: active, inactive, on_leave)
- notes, login_permitted, temporary_password
- timestamps

### 2. roles
**Current Migrations:**
- `2025_10_11_185900_create_roles_table.php` - Base table
- `2025_10_12_060802_add_vessel_id_to_roles_or_remove_legacy_roles.php` - Check if used
- `2025_11_08_214255_check_and_remove_roles_table_if_unused.php` - Remove if unused

**Status:** Verify if still needed (likely yes for system roles)

### 3. user_roles
**Current Migrations:**
- `2025_10_11_185901_create_user_roles_table.php` - Base table

**Status:** No changes needed

### 4. vessels
**Current Migrations:**
- `2025_10_11_185905_create_vessels_table.php` - Base table
- `2025_10_12_031428_add_country_currency_to_vessels_table.php` - country_code, currency_code, status enum change
- `2025_10_12_033217_fix_vessels_foreign_key_constraints.php` - Fix constraints

**Final Schema:**
- id, name, registration_number (unique), vessel_type, capacity, year_built
- status (enum: active, suspended, maintenance, inactive)
- notes, owner_id
- country_code, currency_code
- timestamps, deleted_at

### 5. vessel_role_accesses
**Current Migrations:**
- `2025_10_12_033808_create_vessel_role_accesses_table.php` - Base table

**Status:** No changes needed

### 6. vessel_user_roles
**Current Migrations:**
- `2025_10_12_033815_create_vessel_user_roles_table.php` - Base table

**Status:** No changes needed

### 7. vessel_users
**Current Migrations:**
- `2025_10_12_032213_create_vessel_users_table.php` - Base table
- `2025_10_12_032245_migrate_user_vessels_to_vessel_users.php` - Data migration

**Status:** Keep create, data migration can be removed (development only)

### 8. crew_positions
**Current Migrations:**
- `2025_10_11_185908_create_crew_positions_table.php` - Base table
- `2025_10_12_060303_add_vessel_id_to_crew_positions.php` - Add vessel_id, change unique constraint
- `2025_11_09_232558_add_vessel_role_access_id_to_crew_positions_table.php` - Add vessel_role_access_id

**Final Schema:**
- id, vessel_id (nullable), vessel_role_access_id (nullable)
- name, description
- timestamps
- Unique: (vessel_id, name)

### 9. suppliers
**Current Migrations:**
- `2025_10_11_185909_create_suppliers_table.php` - Base table
- `2025_10_12_055329_add_vessel_id_to_bank_accounts_and_suppliers.php` - Add vessel_id
- `2025_11_07_000001_update_suppliers_table.php` - Updates
- `2025_11_08_220704_add_description_to_suppliers_table.php` - Add description

**Final Schema:** Check all migrations for final state

### 10. transactions
**Current Migrations:**
- `2025_10_11_185911_create_transactions_table.php` - Base table
- `2025_11_08_194206_add_vat_profile_id_to_transactions_table.php` - Add vat_profile_id
- `2025_11_08_214253_remove_vat_rate_id_from_transactions_table.php` - Remove vat_rate_id
- `2025_11_09_002731_remove_bank_account_id_from_transactions_table.php` - Remove bank_account_id
- `2025_11_09_132744_add_marea_id_to_transactions_table.php` - Add marea_id
- `2025_11_09_202957_add_price_per_unit_and_quantity_to_transactions_table.php` - Add price_per_unit, quantity
- `2025_11_09_220115_rename_price_per_unit_to_amount_per_unit_in_transactions_table.php` - Rename
- `2025_11_09_221431_change_quantity_to_integer_in_transactions_table.php` - Change quantity type

**Final Schema:**
- id, transaction_number (unique), vessel_id, marea_id, category_id, supplier_id, crew_member_id
- type (enum: income, expense, transfer)
- amount, amount_per_unit, quantity (integer), currency, house_of_zeros
- vat_profile_id, vat_amount, total_amount
- transaction_date, transaction_month, transaction_year
- description, notes, reference
- is_recurring, recurring_transaction_id
- status (enum: pending, completed, cancelled)
- created_by, timestamps, deleted_at
- NO bank_account_id, NO vat_rate_id

### 11. transaction_categories
**Current Migrations:**
- `2025_10_11_185911_create_transaction_categories_table.php` - Base table

**Status:** No changes needed

### 12. vat_profiles
**Current Migrations:**
- `2025_11_08_194145_create_vat_profiles_table.php` - Base table

**Status:** No changes needed

### 13. recurring_transactions
**Current Migrations:**
- `2025_10_11_185913_create_recurring_transactions_table.php` - Base table
- `2025_11_08_214501_add_vat_profile_id_to_recurring_transactions_and_remove_vat_rate_id.php` - Replace vat_rate_id with vat_profile_id

**Final Schema:** vat_profile_id instead of vat_rate_id

### 14. transaction_files
**Current Migrations:**
- `2025_11_09_005537_create_transaction_files_table.php` - Base table

**Status:** No changes needed

### 15. mareas
**Current Migrations:**
- `2025_11_09_132745_create_mareas_table.php` - Base table
- `2025_11_09_132828_add_distribution_profile_foreign_key_to_mareas_table.php` - Add FK
- `2025_11_09_174118_add_calculation_fields_to_mareas_table.php` - Add use_calculation, currency, house_of_zeros
- `2025_11_09_200437_remove_deleted_at_from_mareas_table.php` - Remove soft deletes
- `2025_11_09_201225_add_deleted_at_back_to_mareas_table.php` - Add soft deletes back

**Final Schema:**
- id, marea_number (unique), vessel_id, name, description
- status (enum: preparing, at_sea, returned, closed, cancelled)
- estimated_departure_date, estimated_return_date
- actual_departure_date, actual_return_date, closed_at
- distribution_profile_id, use_calculation, currency, house_of_zeros
- created_by, timestamps, deleted_at

### 16. marea_crew
**Current Migrations:**
- `2025_11_09_132746_create_marea_crew_table.php` - Base table

**Status:** No changes needed

### 17. marea_quantity_return
**Current Migrations:**
- `2025_11_09_132746_create_marea_quantity_return_table.php` - Base table
- `2025_11_09_161542_remove_price_fields_from_marea_quantity_return_table.php` - Remove price fields

**Final Schema:** Check what fields remain

### 18. marea_distribution_profiles
**Current Migrations:**
- `2025_11_09_132747_create_marea_distribution_profiles_table.php` - Base table

**Status:** No changes needed

### 19. marea_distribution_profile_items
**Current Migrations:**
- `2025_11_09_132747_create_marea_distribution_profile_items_table.php` - Base table

**Status:** No changes needed

### 20. marea_distribution_items
**Current Migrations:**
- `2025_11_09_174118_create_marea_distribution_items_table.php` - Base table

**Status:** No changes needed

### 21. vessel_settings
**Current Migrations:**
- `2025_11_08_201258_create_vessel_settings_table.php` - Base table
- `2025_11_09_194839_add_starting_marea_number_to_vessel_settings_table.php` - Add starting_marea_number

**Final Schema:**
- id, vessel_id (unique), country_code, currency_code, vat_profile_id, starting_marea_number
- timestamps

### 22. salary_compensations
**Current Migrations:**
- `2025_10_24_211316_create_salary_compensations_table.php` - Base table

**Status:** No changes needed

### 23. countries
**Current Migrations:**
- `2025_10_11_234838_create_countries_table.php` - Base table

**Status:** No changes needed

### 24. currencies
**Current Migrations:**
- `2025_10_11_234827_create_currencies_table.php` - Base table

**Status:** No changes needed

### 25. attachments
**Current Migrations:**
- `2025_10_11_185913_create_attachments_table.php` - Base table

**Status:** No changes needed

### 26. monthly_balances
**Current Migrations:**
- `2025_10_11_185914_create_monthly_balances_table.php` - Base table

**Status:** No changes needed

### 27. activity_logs
**Current Migrations:**
- `2025_10_11_185914_create_activity_logs_table.php` - Base table

**Status:** No changes needed

### 28. system_settings
**Current Migrations:**
- `2025_10_11_185914_create_system_settings_table.php` - Base table

**Status:** No changes needed

### 29. account_transfers
**Current Migrations:**
- `2025_10_11_185911_create_account_transfers_table.php` - Base table

**Status:** NOTE: bank_accounts table was dropped, verify if this table is still valid

### 30. Removed Tables
- `bank_accounts` - Dropped in `2025_11_09_002732_drop_bank_accounts_table.php`
- `vat_rates` - Dropped in `2025_11_08_214254_remove_vat_rates_table.php`
- `crew_members` - Dropped in `2025_10_24_205953_drop_crew_members_table.php` (migrated to users)
- `user_vessels` - Replaced by vessel_users

## Migration Order (Dependencies)

1. countries, currencies (no dependencies)
2. users, roles, user_roles
3. vessels (depends on users)
4. vessel_role_accesses
5. vessel_users, vessel_user_roles (depend on users, vessels, vessel_role_accesses)
6. crew_positions (depends on vessels, vessel_role_accesses)
7. suppliers (depends on vessels)
8. transaction_categories
9. vat_profiles (depends on countries)
10. vessel_settings (depends on vessels, vat_profiles, countries, currencies)
11. transactions (depends on vessels, categories, suppliers, users, vat_profiles, mareas, recurring_transactions)
12. recurring_transactions (depends on vessels, categories, suppliers, vat_profiles)
13. transaction_files (depends on transactions)
14. marea_distribution_profiles
15. marea_distribution_profile_items (depends on marea_distribution_profiles)
16. mareas (depends on vessels, marea_distribution_profiles, users)
17. marea_crew (depends on mareas, users)
18. marea_quantity_return (depends on mareas)
19. marea_distribution_items (depends on mareas)
20. salary_compensations (depends on users)
21. attachments, monthly_balances, activity_logs, system_settings
22. account_transfers (verify if still needed - depends on bank_accounts which was dropped)

