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
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_user_type (user_type)
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
    status ENUM('active', 'maintenance', 'inactive') DEFAULT 'active',
    notes TEXT NULL,
    owner_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_registration (registration_number),
    INDEX idx_owner (owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Crew Management

### crew_positions
```sql
CREATE TABLE crew_positions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE, -- captain, sailor, mechanic, cook
    description TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### crew_members
```sql
CREATE TABLE crew_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vessel_id BIGINT UNSIGNED NULL, -- pode não estar vinculado a embarcação
    position_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    document_number VARCHAR(50) UNIQUE NOT NULL, -- NIF, passport, etc
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    date_of_birth DATE NULL,
    hire_date DATE NOT NULL,
    salary_amount BIGINT NOT NULL DEFAULT 0, -- em centavos
    salary_currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
    house_of_zeros TINYINT NOT NULL DEFAULT 2, -- casas decimais (2 = centavos)
    payment_frequency ENUM('weekly', 'biweekly', 'monthly') DEFAULT 'monthly',
    status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE SET NULL,
    FOREIGN KEY (position_id) REFERENCES crew_positions(id) ON DELETE RESTRICT,
    INDEX idx_vessel (vessel_id),
    INDEX idx_status (status),
    INDEX idx_document (document_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

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
```sql
CREATE TABLE bank_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    bank_name VARCHAR(255) NOT NULL,
    account_number VARCHAR(100) NULL,
    iban VARCHAR(34) NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
    house_of_zeros TINYINT NOT NULL DEFAULT 2,
    initial_balance BIGINT NOT NULL DEFAULT 0, -- em centavos
    current_balance BIGINT NOT NULL DEFAULT 0, -- calculado
    status ENUM('active', 'inactive') DEFAULT 'active',
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

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

### vat_rates
```sql
CREATE TABLE vat_rates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    rate DECIMAL(5,2) NOT NULL, -- 23.00, 13.00, 6.00, 0.00
    description TEXT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Core Financial Transactions

### transactions
```sql
CREATE TABLE transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_number VARCHAR(50) UNIQUE NOT NULL, -- gerado automaticamente
    vessel_id BIGINT UNSIGNED NULL,
    bank_account_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NULL,
    crew_member_id BIGINT UNSIGNED NULL, -- se for pagamento de salário
    
    type ENUM('income', 'expense', 'transfer') NOT NULL,
    
    -- Valores monetários
    amount BIGINT NOT NULL, -- valor em inteiro (centavos)
    currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
    house_of_zeros TINYINT NOT NULL DEFAULT 2,
    
    -- IVA
    vat_rate_id BIGINT UNSIGNED NULL,
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
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE RESTRICT,
    FOREIGN KEY (category_id) REFERENCES transaction_categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (crew_member_id) REFERENCES crew_members(id) ON DELETE SET NULL,
    FOREIGN KEY (vat_rate_id) REFERENCES vat_rates(id) ON DELETE SET NULL,
    FOREIGN KEY (recurring_transaction_id) REFERENCES recurring_transactions(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_vessel (vessel_id),
    INDEX idx_bank_account (bank_account_id),
    INDEX idx_category (category_id),
    INDEX idx_type (type),
    INDEX idx_date (transaction_date),
    INDEX idx_month_year (transaction_year, transaction_month),
    INDEX idx_status (status),
    INDEX idx_transaction_number (transaction_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Account Transfers

### account_transfers
```sql
CREATE TABLE account_transfers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    from_account_id BIGINT UNSIGNED NOT NULL,
    to_account_id BIGINT UNSIGNED NOT NULL,
    from_transaction_id BIGINT UNSIGNED NOT NULL,
    to_transaction_id BIGINT UNSIGNED NOT NULL,
    amount BIGINT NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
    house_of_zeros TINYINT NOT NULL DEFAULT 2,
    transfer_date DATE NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (from_account_id) REFERENCES bank_accounts(id) ON DELETE RESTRICT,
    FOREIGN KEY (to_account_id) REFERENCES bank_accounts(id) ON DELETE RESTRICT,
    FOREIGN KEY (from_transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (to_transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    INDEX idx_accounts (from_account_id, to_account_id),
    INDEX idx_transfer_date (transfer_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

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
    
    -- IVA
    vat_rate_id BIGINT UNSIGNED NULL,
    
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
    FOREIGN KEY (vat_rate_id) REFERENCES vat_rates(id) ON DELETE SET NULL,
    
    INDEX idx_next_occurrence (next_occurrence_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

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
```sql
CREATE TABLE monthly_balances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vessel_id BIGINT UNSIGNED NULL,
    bank_account_id BIGINT UNSIGNED NULL,
    month TINYINT NOT NULL, -- 1-12
    year YEAR NOT NULL,
    
    opening_balance BIGINT NOT NULL DEFAULT 0,
    total_income BIGINT NOT NULL DEFAULT 0,
    total_expense BIGINT NOT NULL DEFAULT 0,
    closing_balance BIGINT NOT NULL DEFAULT 0,
    
    currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
    house_of_zeros TINYINT NOT NULL DEFAULT 2,
    
    transaction_count INT NOT NULL DEFAULT 0,
    last_calculated_at TIMESTAMP NULL,
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE CASCADE,
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_balance (vessel_id, bank_account_id, year, month),
    INDEX idx_period (year, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

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
- `transactions` → `bank_accounts` (N:1)
- `transactions` → `transaction_categories` (N:1)
- `transactions` → `suppliers` (N:1)
- `transactions` → `crew_members` (N:1)
- `transactions` → `vat_rates` (N:1)
- `transactions` → `recurring_transactions` (N:1)
- `transactions` → `users` (N:1, created_by)
- `transactions` → `attachments` (1:N polymorphic)

#### Crew Members
- `crew_members` → `vessels` (N:1)
- `crew_members` → `crew_positions` (N:1)
- `crew_members` → `transactions` (1:N)

#### Bank Accounts
- `bank_accounts` → `transactions` (1:N)
- `bank_accounts` → `account_transfers` (1:N, from_account)
- `bank_accounts` → `account_transfers` (1:N, to_account)
- `bank_accounts` → `monthly_balances` (1:N)

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
- `transactions`: `idx_bank_account` for account-specific queries
- `transactions`: `idx_type` for income/expense filtering
- `monthly_balances`: `idx_period` for balance calculations
- `recurring_transactions`: `idx_next_occurrence` for scheduled generation

### Unique Constraints
- `users.email` - unique email addresses
- `vessels.registration_number` - unique vessel registrations
- `crew_members.document_number` - unique crew member documents
- `transactions.transaction_number` - unique transaction numbers
- `monthly_balances.unique_balance` - unique balance per period/account/vessel

## Key Constraints

### Foreign Key Constraints
- All foreign keys use appropriate `ON DELETE` actions:
  - `CASCADE` for dependent data (user_roles, monthly_balances)
  - `SET NULL` for optional relationships (vessel_id, supplier_id)
  - `RESTRICT` for critical relationships (bank_account_id, category_id)

### Check Constraints
- `transactions.amount` >= 0 (positive amounts only)
- `transactions.transaction_month` BETWEEN 1 AND 12
- `transactions.transaction_year` >= 1900
- `vat_rates.rate` >= 0 AND <= 100
- `bank_accounts.current_balance` can be negative (overdraft)

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

1. Authentication tables (`users`, `roles`, `user_roles`)
2. Core entities (`vessels`, `crew_positions`, `crew_members`)
3. Supporting entities (`suppliers`, `bank_accounts`)
4. Configuration (`transaction_categories`, `vat_rates`)
5. Core financial (`transactions`)
6. Advanced features (`account_transfers`, `recurring_transactions`)
7. Supporting features (`attachments`, `monthly_balances`)
8. System features (`activity_logs`, `system_settings`)
