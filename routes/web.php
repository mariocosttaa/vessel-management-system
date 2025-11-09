<?php

use App\Http\Controllers\{
    VesselController,
    CrewMemberController,
    CrewPositionController,
    SupplierController,
    TransactionController,
    VesselSelectorController,
    AttachmentController,
    TestDataController,
    VesselSettingController,
    VesselFileController
};
use App\Http\Middleware\VesselAuthPrivateFiles;
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

// Files Routes
Route::prefix('file/{vesselIdHashed}')->group(function () {

    // Private Files
    Route::get('/company/{filePath}', [VesselFileController::class, 'showPrivate'])
        ->middleware(['auth', VesselAuthPrivateFiles::class])
        ->name('vessel-file-show-private')
        ->where('filePath', '.*');

    // Public Files
    Route::get('/{filePath?}', [VesselFileController::class, 'showPublic'])
        ->name('vessel-file-show-public')
        ->where('filePath', '.*');

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

    // Crew Roles (scoped to current vessel, but includes global roles)
    Route::get('/crew-roles', [CrewPositionController::class, 'index'])->name('panel.crew-roles.index');
    Route::get('/api/crew-roles/{crewPositionId}/details', [CrewPositionController::class, 'details'])->name('panel.api.crew-roles.details');

    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/crew-roles', [CrewPositionController::class, 'store'])->name('panel.crew-roles.store');
        Route::put('/crew-roles/{crewPosition}', [CrewPositionController::class, 'update'])->name('panel.crew-roles.update');
        Route::delete('/crew-roles/{crewPosition}', [CrewPositionController::class, 'destroy'])->name('panel.crew-roles.destroy');
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

    // Transactions (scoped to current vessel)
    Route::get('/transactions', [TransactionController::class, 'index'])->name('panel.transactions.index');
    Route::get('/transactions/create', [TransactionController::class, 'create'])->name('panel.transactions.create');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('panel.transactions.show');
    Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('panel.transactions.edit');
    Route::get('/api/transactions/search', [TransactionController::class, 'search'])->name('panel.api.transactions.search');
    Route::get('/api/transactions/{transaction}/details', [TransactionController::class, 'details'])->name('panel.api.transactions.details');

    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/transactions', [TransactionController::class, 'store'])->name('panel.transactions.store');
        Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('panel.transactions.update');
        Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('panel.transactions.destroy');
        Route::delete('/transactions/{transaction}/files/{transactionFile}', [TransactionController::class, 'deleteFile'])->name('panel.transactions.files.delete');
    });

    // Vessel Settings (scoped to current vessel)
    Route::get('/settings', [VesselSettingController::class, 'edit'])->name('panel.settings.edit');
    Route::patch('/settings/general', [VesselSettingController::class, 'updateGeneral'])->name('panel.settings.update.general');
    Route::patch('/settings/location', [VesselSettingController::class, 'updateLocation'])->name('panel.settings.update.location');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
