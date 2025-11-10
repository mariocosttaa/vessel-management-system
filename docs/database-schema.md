# Database Schema Reference

## Complete Schema Overview

This document provides the complete database schema for the Vessel Management Financial System, including all tables, relationships, indexes, and constraints.

## Authentication and Users

### users
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    user_type ENUM('paid_system', 'employee_of_vessel') DEFAULT 'employee_of_vessel',
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    
    -- Two factor authentication
    two_factor_secret TEXT NULL,
    two_factor_recovery_codes TEXT NULL,
    two_factor_confirmed_at TIMESTAMP NULL,
    
    -- Crew member fields (unified crew system)
    vessel_id BIGINT UNSIGNED NULL,
    position_id BIGINT UNSIGNED NULL,
    phone VARCHAR(50) NULL,
    date_of_birth DATE NULL,
    hire_date DATE NULL,
    house_of_zeros TINYINT DEFAULT 2,
    status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
    notes TEXT NULL,
    
    -- System access fields
    login_permitted BOOLEAN DEFAULT TRUE,
    temporary_password VARCHAR(255) NULL,
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE SET NULL,
    FOREIGN KEY (position_id) REFERENCES crew_positions(id) ON DELETE RESTRICT,
    
    INDEX idx_email (email),
    INDEX idx_user_type (user_type),
    INDEX idx_vessel_status (vessel_id, status),
    INDEX idx_position_status (position_id, status),
    INDEX idx_login_status (login_permitted, status),
    INDEX idx_user_type_login (user_type, login_permitted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### roles
```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE, -- admin, manager, viewer
    description TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### user_roles
```sql
CREATE TABLE user_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_role (user_id, role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Vessel-Specific Role-Based Access Control (RBAC)

### vessel_role_accesses
```sql
CREATE TABLE vessel_role_accesses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE, -- normal, moderator, supervisor, administrator
    display_name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    permissions JSON NOT NULL, -- Array of permission strings
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### vessel_user_roles
```sql
CREATE TABLE vessel_user_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    vessel_id BIGINT UNSIGNED NOT NULL,
    vessel_role_access_id BIGINT UNSIGNED NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE CASCADE,
    FOREIGN KEY (vessel_role_access_id) REFERENCES vessel_role_accesses(id) ON DELETE CASCADE,
    UNIQUE KEY user_vessel_role_unique (user_id, vessel_id, vessel_role_access_id),
    INDEX idx_user_vessel (user_id, vessel_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Vessels

### vessels
```sql
CREATE TABLE vessels (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    registration_number VARCHAR(100) UNIQUE NOT NULL, -- matrícula
    vessel_type VARCHAR(100) NOT NULL, -- cargo, passenger, fishing, yacht
    capacity INT NULL,
    year_built YEAR NULL,
    status ENUM('active', 'suspended', 'maintenance', 'inactive') DEFAULT 'active',
    notes TEXT NULL,
    owner_id BIGINT UNSIGNED NULL,
    country_code VARCHAR(2) NULL,
    currency_code VARCHAR(3) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (country_code) REFERENCES countries(code) ON DELETE SET NULL,
    FOREIGN KEY (currency_code) REFERENCES currencies(code) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_registration (registration_number),
    INDEX idx_owner (owner_id),
    INDEX idx_country_code (country_code),
    INDEX idx_currency_code (currency_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Crew Management

### crew_positions
```sql
CREATE TABLE crew_positions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vessel_id BIGINT UNSIGNED NULL,
    vessel_role_access_id BIGINT UNSIGNED NULL,
    name VARCHAR(100) NOT NULL, -- captain, sailor, mechanic, cook
    description TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE CASCADE,
    FOREIGN KEY (vessel_role_access_id) REFERENCES vessel_role_accesses(id) ON DELETE SET NULL,
    UNIQUE KEY crew_positions_vessel_name_unique (vessel_id, name),
    INDEX idx_vessel_id (vessel_id),
    INDEX idx_vessel_role_access_id (vessel_role_access_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### crew_members
**NOTE**: The `crew_members` table has been removed. Crew member functionality has been unified into the `users` table. See the `users` table schema above for crew member fields.

## Suppliers

### suppliers
```sql
CREATE TABLE suppliers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vessel_id BIGINT UNSIGNED NULL,
    company_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    address TEXT NULL,
    description TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE SET NULL,
    INDEX idx_vessel (vessel_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Bank Accounts

### bank_accounts
**NOTE**: The `bank_accounts` table has been removed from the system. Transactions no longer reference bank accounts.

## Transaction Categories

### transaction_categories
```sql
CREATE TABLE transaction_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    parent_id BIGINT UNSIGNED NULL, -- para subcategorias
    description TEXT NULL,
    color VARCHAR(7) NULL, -- hex color para UI
    is_system BOOLEAN DEFAULT FALSE, -- categorias do sistema não podem ser deletadas
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (parent_id) REFERENCES transaction_categories(id) ON DELETE CASCADE,
    INDEX idx_type (type),
    INDEX idx_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## VAT Configuration

### vat_profiles
```sql
CREATE TABLE vat_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    country_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    percentage DECIMAL(5,2) NOT NULL, -- 23.00, 13.00, 6.00, 0.00
    code VARCHAR(10) NULL, -- e.g., "IVA", "VAT", "GST"
    description TEXT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE SET NULL,
    INDEX idx_country_id (country_id),
    INDEX idx_is_active (is_active),
    INDEX idx_is_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**NOTE**: The `vat_rates` table has been replaced by `vat_profiles` which includes country association and more flexible configuration.

## Core Financial Transactions

### transactions
```sql
CREATE TABLE transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_number VARCHAR(50) UNIQUE NOT NULL, -- gerado automaticamente
    vessel_id BIGINT UNSIGNED NULL,
    marea_id BIGINT UNSIGNED NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NULL,
    crew_member_id BIGINT UNSIGNED NULL, -- se for pagamento de salário
    
    type ENUM('income', 'expense', 'transfer') NOT NULL,
    
    -- Valores monetários
    amount BIGINT NOT NULL, -- valor em inteiro (centavos)
    amount_per_unit BIGINT NULL, -- preço por unidade em centavos
    quantity INT NULL, -- quantidade de itens
    currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
    house_of_zeros TINYINT NOT NULL DEFAULT 2,
    
    -- VAT (vat_rate_id foi removido, usando vat_profile_id)
    vat_profile_id BIGINT UNSIGNED NULL,
    vat_amount BIGINT DEFAULT 0, -- IVA em centavos
    total_amount BIGINT NOT NULL, -- amount + vat_amount
    
    -- Organização temporal
    transaction_date DATE NOT NULL,
    transaction_month TINYINT NOT NULL, -- 1-12
    transaction_year YEAR NOT NULL,
    
    description TEXT NULL,
    notes TEXT NULL,
    reference VARCHAR(100) NULL, -- referência externa (fatura, etc)
    
    -- Despesas recorrentes
    is_recurring BOOLEAN DEFAULT FALSE,
    recurring_transaction_id BIGINT UNSIGNED NULL,
    
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'completed',
    
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE SET NULL,
    FOREIGN KEY (marea_id) REFERENCES mareas(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES transaction_categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (crew_member_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (vat_profile_id) REFERENCES vat_profiles(id) ON DELETE SET NULL,
    FOREIGN KEY (recurring_transaction_id) REFERENCES recurring_transactions(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_vessel (vessel_id),
    INDEX idx_marea (marea_id),
    INDEX idx_category (category_id),
    INDEX idx_type (type),
    INDEX idx_date (transaction_date),
    INDEX idx_month_year (transaction_year, transaction_month),
    INDEX idx_status (status),
    INDEX idx_transaction_number (transaction_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Transaction Files

### transaction_files
```sql
CREATE TABLE transaction_files (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NOT NULL,
    src VARCHAR(500) NOT NULL, -- File path/URL
    name VARCHAR(255) NOT NULL, -- Original file name
    size INT NOT NULL, -- File size in bytes
    type VARCHAR(50) NOT NULL, -- File type (pdf, jpg, png, doc, etc)
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    INDEX idx_transaction_id (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Mareas (Fishing Trips)

### mareas
```sql
CREATE TABLE mareas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    marea_number VARCHAR(50) UNIQUE NOT NULL, -- MARE20250001
    vessel_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NULL, -- Nome opcional da marea
    description TEXT NULL,
    
    -- Status e Ciclo de Vida
    status ENUM('preparing', 'at_sea', 'returned', 'closed', 'cancelled') DEFAULT 'preparing',
    
    -- Datas Estimadas
    estimated_departure_date DATE NULL,
    estimated_return_date DATE NULL,
    
    -- Datas Reais
    actual_departure_date DATE NULL,
    actual_return_date DATE NULL,
    closed_at TIMESTAMP NULL,
    
    -- Perfil de Distribuição Financeira
    distribution_profile_id BIGINT UNSIGNED NULL,
    
    -- Calculation fields
    use_calculation BOOLEAN DEFAULT TRUE,
    currency VARCHAR(3) NULL,
    house_of_zeros TINYINT DEFAULT 2,
    
    -- Metadados
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE CASCADE,
    FOREIGN KEY (distribution_profile_id) REFERENCES marea_distribution_profiles(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_vessel_id (vessel_id),
    INDEX idx_status (status),
    INDEX idx_marea_number (marea_number),
    INDEX idx_dates (estimated_departure_date, actual_departure_date),
    INDEX idx_distribution_profile_id (distribution_profile_id),
    INDEX idx_use_calculation (use_calculation),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### marea_crew
```sql
CREATE TABLE marea_crew (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    marea_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (marea_id) REFERENCES mareas(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_marea_crew (marea_id, user_id),
    INDEX idx_marea_id (marea_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### marea_quantity_return
```sql
CREATE TABLE marea_quantity_return (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    marea_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL, -- Nome do produto/peixe
    quantity DECIMAL(10,2) NOT NULL, -- Quantidade retornada
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (marea_id) REFERENCES mareas(id) ON DELETE CASCADE,
    INDEX idx_marea_id (marea_id),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### marea_distribution_profiles
```sql
CREATE TABLE marea_distribution_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL, -- Nome do perfil
    description TEXT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    is_system BOOLEAN DEFAULT FALSE, -- Perfis do sistema não podem ser deletados
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_is_default (is_default),
    INDEX idx_is_system (is_system),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### marea_distribution_profile_items
```sql
CREATE TABLE marea_distribution_profile_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    distribution_profile_id BIGINT UNSIGNED NOT NULL,
    order_index INT NOT NULL, -- Ordem de execução
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    value_type ENUM('base_total_income', 'base_total_expense', 'fixed_amount', 'percentage_of_income', 'percentage_of_expense', 'reference_item') NOT NULL,
    value_amount DECIMAL(15,2) NULL, -- Valor fixo ou percentual
    reference_item_id BIGINT UNSIGNED NULL, -- ID do item referenciado
    operation ENUM('set', 'add', 'subtract', 'multiply', 'divide') DEFAULT 'set',
    reference_operation_item_id BIGINT UNSIGNED NULL, -- ID do item para operação
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (distribution_profile_id) REFERENCES marea_distribution_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (reference_item_id) REFERENCES marea_distribution_profile_items(id) ON DELETE SET NULL,
    FOREIGN KEY (reference_operation_item_id) REFERENCES marea_distribution_profile_items(id) ON DELETE SET NULL,
    INDEX idx_distribution_profile_id (distribution_profile_id),
    INDEX idx_order (distribution_profile_id, order_index),
    INDEX idx_reference_item_id (reference_item_id),
    INDEX idx_reference_operation_item_id (reference_operation_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### marea_distribution_items
```sql
CREATE TABLE marea_distribution_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    marea_id BIGINT UNSIGNED NOT NULL,
    profile_item_id BIGINT UNSIGNED NULL, -- Reference to profile item
    order_index INT NOT NULL, -- Ordem de execução
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    value_type ENUM('base_total_income', 'base_total_expense', 'fixed_amount', 'percentage_of_income', 'percentage_of_expense', 'reference_item') NOT NULL,
    value_amount DECIMAL(15,2) NULL, -- Valor fixo ou percentual
    reference_item_id BIGINT UNSIGNED NULL,
    operation ENUM('set', 'add', 'subtract', 'multiply', 'divide') DEFAULT 'set',
    reference_operation_item_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (marea_id) REFERENCES mareas(id) ON DELETE CASCADE,
    FOREIGN KEY (profile_item_id) REFERENCES marea_distribution_profile_items(id) ON DELETE SET NULL,
    FOREIGN KEY (reference_item_id) REFERENCES marea_distribution_items(id) ON DELETE SET NULL,
    FOREIGN KEY (reference_operation_item_id) REFERENCES marea_distribution_items(id) ON DELETE SET NULL,
    INDEX idx_marea_id (marea_id),
    INDEX idx_marea_order (marea_id, order_index),
    INDEX idx_profile_item_id (profile_item_id),
    INDEX idx_reference_item_id (reference_item_id),
    INDEX idx_reference_operation_item_id (reference_operation_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Vessel Settings

### vessel_settings
```sql
CREATE TABLE vessel_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vessel_id BIGINT UNSIGNED UNIQUE NOT NULL,
    country_code VARCHAR(2) NULL,
    currency_code VARCHAR(3) NULL,
    vat_profile_id BIGINT UNSIGNED NULL,
    starting_marea_number INT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE CASCADE,
    FOREIGN KEY (vat_profile_id) REFERENCES vat_profiles(id) ON DELETE SET NULL,
    FOREIGN KEY (country_code) REFERENCES countries(code) ON DELETE SET NULL,
    FOREIGN KEY (currency_code) REFERENCES currencies(code) ON DELETE SET NULL,
    INDEX idx_country_code (country_code),
    INDEX idx_currency_code (currency_code),
    INDEX idx_vat_profile_id (vat_profile_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Salary Compensations

### salary_compensations
```sql
CREATE TABLE salary_compensations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    compensation_type ENUM('fixed', 'percentage') DEFAULT 'fixed',
    fixed_amount BIGINT NULL COMMENT 'Fixed salary amount in cents',
    percentage DECIMAL(5,2) NULL COMMENT 'Percentage of total revenue (0.00-100.00)',
    currency VARCHAR(3) DEFAULT 'EUR',
    payment_frequency ENUM('weekly', 'bi_weekly', 'monthly', 'quarterly', 'annually') DEFAULT 'monthly',
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_compensation_type_active (compensation_type, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Account Transfers

### account_transfers
**NOTE**: This table references `bank_accounts` which has been removed from the system. This table needs to be reviewed and either removed or updated to work without bank accounts.

## Recurring Transactions

### recurring_transactions
```sql
CREATE TABLE recurring_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vessel_id BIGINT UNSIGNED NULL,
    bank_account_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NULL,
    
    name VARCHAR(255) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    
    -- Valores
    amount BIGINT NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
    house_of_zeros TINYINT NOT NULL DEFAULT 2,
    
    -- VAT (vat_rate_id foi removido, usando vat_profile_id)
    vat_profile_id BIGINT UNSIGNED NULL,
    
    -- Recorrência
    frequency ENUM('daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'semi_annual', 'annual') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    next_occurrence_date DATE NOT NULL,
    last_generated_date DATE NULL,
    
    description TEXT NULL,
    auto_generate BOOLEAN DEFAULT TRUE, -- gera automaticamente
    
    status ENUM('active', 'paused', 'completed') DEFAULT 'active',
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE SET NULL,
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE RESTRICT,
    FOREIGN KEY (category_id) REFERENCES transaction_categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (vat_profile_id) REFERENCES vat_profiles(id) ON DELETE SET NULL,
    
    INDEX idx_next_occurrence (next_occurrence_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**NOTE**: This table still references `bank_accounts` which has been removed. This needs to be reviewed and updated.

## Attachments

### attachments
```sql
CREATE TABLE attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attachable_type VARCHAR(255) NOT NULL, -- Transaction, CrewMember, etc
    attachable_id BIGINT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL, -- pdf, jpg, png, etc
    file_size INT NOT NULL, -- em bytes
    description TEXT NULL,
    uploaded_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_attachable (attachable_type, attachable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Monthly Balances (Performance Optimization)

### monthly_balances
**NOTE**: This table references `bank_accounts` which has been removed from the system. This table needs to be reviewed and updated to work without bank accounts, or the `bank_account_id` field should be removed.

## Activity Logs

### activity_logs
```sql
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    subject_type VARCHAR(255) NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(50) NOT NULL, -- created, updated, deleted
    description TEXT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_subject (subject_type, subject_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## System Settings

### system_settings
```sql
CREATE TABLE system_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key VARCHAR(100) UNIQUE NOT NULL,
    value TEXT NULL,
    type VARCHAR(50) NOT NULL, -- string, integer, boolean, json
    description TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_key (key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Relationship Mappings

### Primary Relationships

#### Vessels
- `vessels` → `crew_members` (1:N)
- `vessels` → `transactions` (1:N)
- `vessels` → `monthly_balances` (1:N)
- `vessels` → `attachments` (1:N polymorphic)

#### Transactions
- `transactions` → `vessels` (N:1)
- `transactions` → `mareas` (N:1)
- `transactions` → `transaction_categories` (N:1)
- `transactions` → `suppliers` (N:1)
- `transactions` → `users` (N:1, crew_member_id)
- `transactions` → `vat_profiles` (N:1)
- `transactions` → `recurring_transactions` (N:1)
- `transactions` → `users` (N:1, created_by)
- `transactions` → `transaction_files` (1:N)

#### Mareas
- `mareas` → `vessels` (N:1)
- `mareas` → `marea_distribution_profiles` (N:1)
- `mareas` → `users` (N:1, created_by)
- `mareas` → `marea_crew` (1:N)
- `mareas` → `marea_quantity_return` (1:N)
- `mareas` → `marea_distribution_items` (1:N)
- `mareas` → `transactions` (1:N)

#### Users (Crew Members)
- `users` → `vessels` (N:1, as crew member)
- `users` → `crew_positions` (N:1)
- `users` → `marea_crew` (1:N)
- `users` → `transactions` (1:N, as crew_member_id)
- `users` → `salary_compensations` (1:N)

#### Suppliers
- `suppliers` → `transactions` (1:N)
- `suppliers` → `recurring_transactions` (1:N)

#### Users
- `users` → `user_roles` (1:N)
- `users` → `transactions` (1:N, created_by)
- `users` → `attachments` (1:N, uploaded_by)
- `users` → `activity_logs` (1:N)

## Index Strategies

### Performance Indexes
- `transactions`: `idx_month_year` for period-based queries
- `transactions`: `idx_vessel` for vessel-specific queries
- `transactions`: `idx_marea` for marea-specific queries
- `transactions`: `idx_type` for income/expense filtering
- `mareas`: `idx_dates` for date-based queries
- `mareas`: `idx_status` for status filtering
- `recurring_transactions`: `idx_next_occurrence` for scheduled generation
- `marea_distribution_items`: `idx_marea_order` for ordered calculations

### Unique Constraints
- `users.email` - unique email addresses
- `vessels.registration_number` - unique vessel registrations
- `vessel_settings.vessel_id` - one settings record per vessel
- `crew_positions.vessel_id + name` - unique position name per vessel
- `transactions.transaction_number` - unique transaction numbers
- `mareas.marea_number` - unique marea numbers
- `marea_crew.marea_id + user_id` - unique crew assignment per marea

## Key Constraints

### Foreign Key Constraints
- All foreign keys use appropriate `ON DELETE` actions:
  - `CASCADE` for dependent data (user_roles, marea_crew, marea_distribution_items)
  - `SET NULL` for optional relationships (vessel_id, supplier_id, marea_id)
  - `RESTRICT` for critical relationships (category_id)

### Check Constraints
- `transactions.amount` >= 0 (positive amounts only)
- `transactions.transaction_month` BETWEEN 1 AND 12
- `transactions.transaction_year` >= 1900
- `vat_profiles.percentage` >= 0 AND <= 100
- `salary_compensations.percentage` >= 0 AND <= 100

## Data Types Summary

### Money Fields
- All monetary amounts: `BIGINT` (supports up to €92,233,720,368.54)
- Currency codes: `VARCHAR(3)` (ISO 4217)
- Decimal places: `TINYINT` (0-255)

### Date Fields
- Transaction dates: `DATE`
- Timestamps: `TIMESTAMP`
- Years: `YEAR` (1901-2155)

### Status Fields
- Use `ENUM` for predefined status values
- Consistent naming: `active`, `inactive`, `pending`, `completed`, `cancelled`

### Text Fields
- Short text: `VARCHAR(255)` or smaller
- Long text: `TEXT`
- JSON data: `JSON` (MySQL 5.7+)

## Migration Order

1. **Independent tables** (`countries`, `currencies`)
2. **Authentication tables** (`users`, `roles`, `user_roles`)
3. **Core entities** (`vessels`, `crew_positions`)
4. **Vessel RBAC** (`vessel_role_accesses`, `vessel_user_roles`, `vessel_users`)
5. **Supporting entities** (`suppliers`)
6. **Configuration** (`transaction_categories`, `vat_profiles`)
7. **Vessel settings** (`vessel_settings`)
8. **Marea system** (`marea_distribution_profiles`, `marea_distribution_profile_items`, `mareas`, `marea_crew`, `marea_quantity_return`, `marea_distribution_items`)
9. **Core financial** (`transactions`, `transaction_files`, `recurring_transactions`)
10. **Salary system** (`salary_compensations`)
11. **Supporting features** (`attachments`, `monthly_balances`)
12. **System features** (`activity_logs`, `system_settings`)
