<?php

use App\Http\Controllers\{
    VesselController,
    CrewMemberController,
    SupplierController,
    BankAccountController,
    VesselSelectorController,
    AttachmentController,
    TestDataController
};
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

// Redirect root to panel if authenticated, otherwise to auth login
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('panel.index');
    }
    return redirect()->route('login');
});

// Panel Routes (vessel selector and vessel management)
Route::middleware(['auth', 'verified'])->prefix('panel')->group(function () {
    Route::get('/', [VesselSelectorController::class, 'index'])->name('panel.index');
    Route::post('/select', [VesselSelectorController::class, 'select'])->name('panel.select');

    // Profile Management (moved from settings)
    Route::get('/profile', [App\Http\Controllers\Settings\ProfileController::class, 'edit'])->name('panel.profile.edit');
    Route::patch('/profile', [App\Http\Controllers\Settings\ProfileController::class, 'update'])->name('panel.profile.update');
    Route::delete('/profile', [App\Http\Controllers\Settings\ProfileController::class, 'destroy'])->name('panel.profile.destroy');
    Route::put('/password', [App\Http\Controllers\Settings\PasswordController::class, 'update'])->name('panel.password.update');

    // Vessel Management (panel level)
    Route::get('/vessel/create', [VesselController::class, 'create'])->name('panel.vessel.create');
    Route::post('/vessel', [VesselController::class, 'store'])->name('panel.vessel.store');
    Route::get('/vessel/{vessel}/edit', [VesselController::class, 'edit'])->name('panel.vessel.edit');
    Route::put('/vessel/{vessel}', [VesselController::class, 'update'])->name('panel.vessel.update');
    Route::delete('/vessel/{vessel}', [VesselController::class, 'destroy'])->name('panel.vessel.destroy');

    // Attachment Management (panel level)
    Route::get('/attachments', [AttachmentController::class, 'index'])->name('panel.attachments.index');
    Route::post('/attachments', [AttachmentController::class, 'store'])->name('panel.attachments.store');
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'show'])->name('panel.attachments.show');
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('panel.attachments.download');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('panel.attachments.destroy');

    // Test Data Routes (only in development/testing)
    if (app()->environment(['local', 'testing'])) {
        Route::get('/test-data', [TestDataController::class, 'index'])->name('panel.test-data');
        Route::get('/test-permissions', [TestDataController::class, 'permissions'])->name('panel.test-permissions');
    }
});

// All panel routes require vessel access
Route::middleware(['auth', 'verified', 'vessel.access'])->prefix('panel/{vessel}')->group(function () {

    // Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('panel.dashboard');

    // Vessels (scoped to current vessel) - only show current vessel info
    Route::get('/vessels', [VesselController::class, 'index'])->name('panel.vessels.index');
    Route::get('/vessels/show', [VesselController::class, 'show'])->name('panel.vessels.show');
    Route::get('/api/vessels/search', [VesselController::class, 'search'])->name('panel.api.vessels.search');

    // Crew Members (scoped to current vessel)
    Route::get('/crew-members', [CrewMemberController::class, 'index'])->name('panel.crew-members.index');
    Route::get('/crew-members/create', [CrewMemberController::class, 'create'])->name('panel.crew-members.create');
    Route::get('/crew-members/{crewMember}', [CrewMemberController::class, 'show'])->name('panel.crew-members.show');
    Route::get('/crew-members/{crewMember}/edit', [CrewMemberController::class, 'edit'])->name('panel.crew-members.edit');
    Route::get('/api/crew-members/search', [CrewMemberController::class, 'search'])->name('panel.api.crew-members.search');

    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/crew-members', [CrewMemberController::class, 'store'])->name('panel.crew-members.store');
        Route::put('/crew-members/{crewMember}', [CrewMemberController::class, 'update'])->name('panel.crew-members.update');
        Route::delete('/crew-members/{crewMember}', [CrewMemberController::class, 'destroy'])->name('panel.crew-members.destroy');
    });

    // Suppliers (global but vessel-aware)
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('panel.suppliers.index');
    Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('panel.suppliers.create');
    Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->name('panel.suppliers.show');
    Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('panel.suppliers.edit');
    Route::get('/api/suppliers/search', [SupplierController::class, 'search'])->name('panel.api.suppliers.search');

    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('panel.suppliers.store');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('panel.suppliers.update');
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('panel.suppliers.destroy');
    });

    // Bank Accounts (global but vessel-aware)
    Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('panel.bank-accounts.index');
    Route::get('/bank-accounts/create', [BankAccountController::class, 'create'])->name('panel.bank-accounts.create');
    Route::get('/bank-accounts/{bankAccount}', [BankAccountController::class, 'show'])->name('panel.bank-accounts.show');
    Route::get('/bank-accounts/{bankAccount}/edit', [BankAccountController::class, 'edit'])->name('panel.bank-accounts.edit');
    Route::get('/api/bank-accounts/search', [BankAccountController::class, 'search'])->name('panel.api.bank-accounts.search');
    Route::get('/api/bank-accounts/{bankAccount}/details', [BankAccountController::class, 'details'])->name('panel.api.bank-accounts.details');

    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/bank-accounts', [BankAccountController::class, 'store'])->name('panel.bank-accounts.store');
        Route::put('/bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->name('panel.bank-accounts.update');
        Route::delete('/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('panel.bank-accounts.destroy');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
