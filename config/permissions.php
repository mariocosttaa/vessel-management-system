<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vessel Role Permissions
    |--------------------------------------------------------------------------
    |
    | This file defines the permissions for each vessel role in the system.
    | Permissions are organized by role (Administrator, Supervisor, Moderator, Normal User).
    |
    | Permission Structure:
    | - Each permission follows the pattern: 'resource.action'
    | - Resources: vessels, crew, crew-roles, suppliers, bank-accounts, transactions, reports, settings, users
    | - Actions: view, create, edit, delete, access, manage
    |
    | Role Hierarchy:
    | - Administrator: Full control over the vessel (owner-level permissions)
    | - Supervisor: Can view, edit basic and advanced vessel data
    | - Moderator: Can view and edit basic vessel data
    | - Normal User: View-only access to vessel data
    |
    */

    /**
     * Default permissions for users without vessel access or unknown roles.
     * All permissions are set to false for security.
     */
    'default' => [
        'vessels.create' => false,
        'vessels.edit' => false,
        'vessels.delete' => false,
        'vessels.view' => false,
        'crew.create' => false,
        'crew.edit' => false,
        'crew.delete' => false,
        'crew.view' => false,
        'crew-roles.create' => false,
        'crew-roles.edit' => false,
        'crew-roles.delete' => false,
        'crew-roles.view' => false,
        'suppliers.create' => false,
        'suppliers.edit' => false,
        'suppliers.delete' => false,
        'suppliers.view' => false,
        'bank-accounts.create' => false,
        'bank-accounts.edit' => false,
        'bank-accounts.delete' => false,
        'bank-accounts.view' => false,
        'movimentations.create' => false,
        'movimentations.edit' => false,
        'movimentations.delete' => false,
        'movimentations.view' => false,
        'mareas.create' => false,
        'mareas.edit' => false,
        'mareas.delete' => false,
        'mareas.view' => false,
        'mareas.manage-status' => false,
        'maintenances.create' => false,
        'maintenances.edit' => false,
        'maintenances.delete' => false,
        'maintenances.view' => false,
        'distribution-profiles.create' => false,
        'distribution-profiles.edit' => false,
        'distribution-profiles.delete' => false,
        'distribution-profiles.view' => false,
        'reports.access' => false, // No access to financial reports or VAT reports
        'settings.access' => false,
        'users.manage' => false,
        'recycle_bin.view' => false,
        'recycle_bin.restore' => false,
        'recycle_bin.delete' => false,
    ],

    /**
     * Administrator permissions - Full control over the vessel.
     */
    'Administrator' => [
        'vessels.create' => true,
        'vessels.edit' => true,
        'vessels.delete' => true,
        'vessels.view' => true,
        'crew.create' => true,
        'crew.edit' => true,
        'crew.delete' => true,
        'crew.view' => true,
        'crew-roles.create' => true,
        'crew-roles.edit' => true,
        'crew-roles.delete' => true,
        'crew-roles.view' => true,
        'suppliers.create' => true,
        'suppliers.edit' => true,
        'suppliers.delete' => true,
        'suppliers.view' => true,
        'bank-accounts.create' => true,
        'bank-accounts.edit' => true,
        'bank-accounts.delete' => true,
        'bank-accounts.view' => true,
        'movimentations.create' => true,
        'movimentations.edit' => true,
        'movimentations.delete' => true,
        'movimentations.view' => true,
        'mareas.create' => true,
        'mareas.edit' => true,
        'mareas.delete' => true,
        'mareas.view' => true,
        'mareas.manage-status' => true,
        'maintenances.create' => true,
        'maintenances.edit' => true,
        'maintenances.delete' => true,
        'maintenances.view' => true,
        'distribution-profiles.create' => true,
        'distribution-profiles.edit' => true,
        'distribution-profiles.delete' => true,
        'distribution-profiles.view' => true,
        'reports.access' => true,
        'settings.access' => true,
        'users.manage' => true,
        'recycle_bin.view' => true,
        'recycle_bin.restore' => true,
        'recycle_bin.delete' => true,
    ],

    /**
     * Supervisor permissions - Can view, edit basic and advanced vessel data.
     */
    'Supervisor' => [
        'vessels.create' => false,
        'vessels.edit' => true,
        'vessels.delete' => false,
        'vessels.view' => true,
        'crew.create' => true,
        'crew.edit' => true,
        'crew.delete' => true,
        'crew.view' => true,
        'crew-roles.create' => true,
        'crew-roles.edit' => true,
        'crew-roles.delete' => true,
        'crew-roles.view' => true,
        'suppliers.create' => true,
        'suppliers.edit' => true,
        'suppliers.delete' => false,
        'suppliers.view' => true,
        'bank-accounts.create' => true,
        'bank-accounts.edit' => true,
        'bank-accounts.delete' => false,
        'bank-accounts.view' => true,
        'movimentations.create' => true,
        'movimentations.edit' => true,
        'movimentations.delete' => false,
        'movimentations.view' => true,
        'mareas.create' => true,
        'mareas.edit' => true,
        'mareas.delete' => false,
        'mareas.view' => true,
        'mareas.manage-status' => true,
        'maintenances.create' => true,
        'maintenances.edit' => true,
        'maintenances.delete' => false,
        'maintenances.view' => true,
        'distribution-profiles.create' => true,
        'distribution-profiles.edit' => true,
        'distribution-profiles.delete' => false,
        'distribution-profiles.view' => true,
        'reports.access' => true,
        'settings.access' => true,
        'users.manage' => false,
        'recycle_bin.view' => true,
        'recycle_bin.restore' => true,
        'recycle_bin.delete' => false,
    ],

    /**
     * Moderator permissions - Can view and edit basic vessel data.
     * Note: Moderators and Administrators can access crew-roles, suppliers, and bank-accounts.
     * Normal users cannot view these resources.
     */
    'Moderator' => [
        'vessels.create' => false,
        'vessels.edit' => true,
        'vessels.delete' => false,
        'vessels.view' => true,
        'crew.create' => false,
        'crew.edit' => true,
        'crew.delete' => false,
        'crew.view' => true,
        'crew-roles.create' => false,
        'crew-roles.edit' => true,
        'crew-roles.delete' => false,
        'crew-roles.view' => true, // Moderators can view crew roles
        'suppliers.create' => false,
        'suppliers.edit' => true,
        'suppliers.delete' => false,
        'suppliers.view' => true, // Moderators can view suppliers
        'bank-accounts.create' => false,
        'bank-accounts.edit' => true,
        'bank-accounts.delete' => false,
        'bank-accounts.view' => true, // Moderators can view bank accounts
        'movimentations.create' => false,
        'movimentations.edit' => true,
        'movimentations.delete' => false,
        'movimentations.view' => true,
        'mareas.create' => false,
        'mareas.edit' => true,
        'mareas.delete' => false,
        'mareas.view' => true,
        'mareas.manage-status' => false,
        'maintenances.create' => false,
        'maintenances.edit' => true,
        'maintenances.delete' => false,
        'maintenances.view' => true,
        'distribution-profiles.create' => false,
        'distribution-profiles.edit' => false,
        'distribution-profiles.delete' => false,
        'distribution-profiles.view' => true,
        'reports.access' => true,
        'settings.access' => false,
        'users.manage' => false,
        'recycle_bin.view' => false,
        'recycle_bin.restore' => false,
        'recycle_bin.delete' => false,
    ],

    /**
     * Normal User permissions - View-only access to vessel data.
     * Note: Normal users cannot view crew-roles, suppliers, bank-accounts, financial reports, VAT reports, distribution profiles, recycle bin, or audit logs.
     * Only moderators and administrators have access to these resources.
     */
    'Normal User' => [
        'vessels.create' => false,
        'vessels.edit' => false,
        'vessels.delete' => false,
        'vessels.view' => true,
        'crew.create' => false,
        'crew.edit' => false,
        'crew.delete' => false,
        'crew.view' => true,
        'crew-roles.create' => false,
        'crew-roles.edit' => false,
        'crew-roles.delete' => false,
        'crew-roles.view' => false, // Normal users cannot view crew roles
        'suppliers.create' => false,
        'suppliers.edit' => false,
        'suppliers.delete' => false,
        'suppliers.view' => false, // Normal users cannot view suppliers
        'bank-accounts.create' => false,
        'bank-accounts.edit' => false,
        'bank-accounts.delete' => false,
        'bank-accounts.view' => false, // Normal users cannot view bank accounts
        'movimentations.create' => false,
        'movimentations.edit' => false,
        'movimentations.delete' => false,
        'movimentations.view' => true, // Can view transactions but not financial reports
        'mareas.create' => false,
        'mareas.edit' => false,
        'mareas.delete' => false,
        'mareas.view' => true,
        'mareas.manage-status' => false,
        'maintenances.create' => false,
        'maintenances.edit' => false,
        'maintenances.delete' => false,
        'maintenances.view' => true,
        'distribution-profiles.create' => false,
        'distribution-profiles.edit' => false,
        'distribution-profiles.delete' => false,
        'distribution-profiles.view' => false, // Normal users cannot view distribution profiles
        'reports.access' => false, // Normal users cannot access financial reports or VAT reports
        'settings.access' => false,
        'users.manage' => false,
        'recycle_bin.view' => false,
        'recycle_bin.restore' => false,
        'recycle_bin.delete' => false,
    ],

];

