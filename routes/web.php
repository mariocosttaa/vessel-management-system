<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CrewMemberController;
use App\Http\Controllers\CrewPositionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MovimentationController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TermsOfServiceController;
use App\Http\Controllers\TestDataController;
use App\Http\Controllers\VatReportController;
use App\Http\Controllers\VesselController;
use App\Http\Controllers\VesselFileController;
use App\Http\Controllers\VesselSelectorController;
use App\Http\Controllers\VesselSettingController;
use App\Http\Middleware\VesselAuthPrivateFiles;
use Illuminate\Support\Facades\Route;

// Landing page route
Route::get('/', [LandingController::class, 'index'])->name('landing');

// Legal pages
Route::get('/privacy-policy', [PrivacyPolicyController::class, 'index'])->name('privacy-policy');
Route::get('/terms-of-service', [TermsOfServiceController::class, 'index'])->name('terms-of-service');

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
    Route::put('/language', [App\Http\Controllers\Settings\ProfileController::class, 'updateLanguage'])->name('panel.language.update');

    // OAuth account linking/disconnecting
    Route::post('/profile/oauth/{provider}/disconnect', [App\Http\Controllers\Settings\ProfileController::class, 'disconnectOAuth'])
        ->where('provider', 'google|microsoft')
        ->name('panel.profile.oauth.disconnect');

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
        Route::get('/email-test', function () {
            return view('emails.test', [
                'title' => 'Email Template Test - ' . config('app.name'),
            ]);
        })->name('panel.email-test');
        Route::get('/email-notification-test', function () {
            return view('emails.notification-example', [
                'title' => 'Movimentation Created - ' . config('app.name'),
            ]);
        })->name('panel.email-notification-test');
    }
});

// All panel routes require vessel access
// Note: {vessel} parameter is handled by EnsureVesselAccess middleware, not route model binding
Route::middleware(['auth', 'verified', 'vessel.access'])->prefix('panel/{vessel}')->where(['vessel' => '[^/]+'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('panel.dashboard');

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
    Route::post('/api/crew-members/check-email', [CrewMemberController::class, 'checkEmail'])->name('panel.api.crew-members.check-email');

    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/crew-members', [CrewMemberController::class, 'store'])->name('panel.crew-members.store');
        Route::put('/crew-members/{crewMember}', [CrewMemberController::class, 'update'])->name('panel.crew-members.update');
        Route::delete('/crew-members/{crewMember}', [CrewMemberController::class, 'destroy'])->name('panel.crew-members.destroy');
        Route::post('/crew-members/{crewMember}/cancel-invitation', [CrewMemberController::class, 'cancelInvitation'])->name('panel.crew-members.cancel-invitation');
        Route::post('/crew-members/{crewMember}/resend-invitation', [CrewMemberController::class, 'resendInvitation'])->name('panel.crew-members.resend-invitation');
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

    // Movimentations (scoped to current vessel)
    // IMPORTANT: More specific routes must come before general routes
    Route::get('/movimentations', [MovimentationController::class, 'index'])->name('panel.movimentations.index');
    Route::get('/movimentations/create', [MovimentationController::class, 'create'])->name('panel.movimentations.create');
    Route::get('/movimentations/history', [MovimentationController::class, 'history'])->name('panel.movimentations.history');
    Route::get('/movimentations/history/{year}/{month}', [MovimentationController::class, 'historyMonth'])
        ->where(['year' => '[0-9]{4}', 'month' => '[0-9]{1,2}'])
        ->name('panel.movimentations.history.month');
    Route::get('/movimentations/history/download-pdf', [MovimentationController::class, 'downloadPdf'])
        ->middleware('throttle:10,1')
        ->name('panel.movimentations.history.download-pdf');
    Route::get('/movimentations/history/{year}/{month}/download-pdf', [MovimentationController::class, 'downloadPdfMonth'])
        ->where(['year' => '[0-9]{4}', 'month' => '[0-9]{1,2}'])
        ->middleware('throttle:10,1')
        ->name('panel.movimentations.history.month.download-pdf');
    Route::get('/movimentations/download-pdf', [MovimentationController::class, 'downloadPdfFiltered'])
        ->middleware('throttle:10,1')
        ->name('panel.movimentations.download-pdf');
    Route::get('/movimentations/{movimentationId}', [MovimentationController::class, 'show'])->name('panel.movimentations.show');
    Route::get('/movimentations/{movimentationId}/edit', [MovimentationController::class, 'edit'])->name('panel.movimentations.edit');
    Route::get('/api/movimentations/search', [MovimentationController::class, 'search'])->name('panel.api.movimentations.search');
    Route::get('/api/movimentations/{movimentationId}/details', [MovimentationController::class, 'details'])->name('panel.api.movimentations.details');

    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/movimentations', [MovimentationController::class, 'store'])->name('panel.movimentations.store');
        Route::put('/movimentations/{movimentationId}', [MovimentationController::class, 'update'])->name('panel.movimentations.update');
        Route::delete('/movimentations/{movimentationId}', [MovimentationController::class, 'destroy'])->name('panel.movimentations.destroy');
        Route::delete('/movimentations/{movimentationId}/files/{fileId}', [MovimentationController::class, 'deleteFile'])->name('panel.movimentations.files.delete');
    });

    // Vessel Settings (scoped to current vessel)
    Route::get('/settings', [VesselSettingController::class, 'edit'])->name('panel.settings.edit');
    Route::match(['patch', 'post'], '/settings/general', [VesselSettingController::class, 'updateGeneral'])->name('panel.settings.update.general');
    Route::patch('/settings/location', [VesselSettingController::class, 'updateLocation'])->name('panel.settings.update.location');

    // Categories (scoped to current vessel)
    Route::post('/categories', [App\Http\Controllers\MovimentationCategoryController::class, 'store'])->name('panel.categories.store');

    // Mareas (scoped to current vessel)
    Route::get('/mareas', [App\Http\Controllers\MareaController::class, 'index'])->name('panel.mareas.index');
    Route::get('/mareas/create', [App\Http\Controllers\MareaController::class, 'create'])->name('panel.mareas.create');
    Route::post('/mareas', [App\Http\Controllers\MareaController::class, 'store'])->name('panel.mareas.store');
    // Use string parameter instead of Route Model Binding to avoid conflicts
    Route::get('/mareas/{mareaId}', [App\Http\Controllers\MareaController::class, 'show'])->name('panel.mareas.show');
    Route::get('/mareas/{mareaId}/edit', [App\Http\Controllers\MareaController::class, 'edit'])->name('panel.mareas.edit');
    Route::put('/mareas/{mareaId}', [App\Http\Controllers\MareaController::class, 'update'])->name('panel.mareas.update');
    Route::delete('/mareas/{mareaId}', [App\Http\Controllers\MareaController::class, 'destroy'])->name('panel.mareas.destroy');

    // Marea Actions
    Route::post('/mareas/{mareaId}/mark-at-sea', [App\Http\Controllers\MareaController::class, 'markAtSea'])->name('panel.mareas.mark-at-sea');
    Route::post('/mareas/{mareaId}/mark-returned', [App\Http\Controllers\MareaController::class, 'markReturned'])->name('panel.mareas.mark-returned');
    Route::post('/mareas/{mareaId}/close', [App\Http\Controllers\MareaController::class, 'close'])->name('panel.mareas.close');
    Route::post('/mareas/{mareaId}/cancel', [App\Http\Controllers\MareaController::class, 'cancel'])->name('panel.mareas.cancel');

    // Marea Management
    Route::post('/mareas/{mareaId}/add-movimentation', [App\Http\Controllers\MareaController::class, 'addTransaction'])->name('panel.mareas.add-movimentation');
    Route::delete('/mareas/{mareaId}/remove-movimentation/{transaction}', [App\Http\Controllers\MareaController::class, 'removeTransaction'])->name('panel.mareas.remove-movimentation');
    Route::post('/mareas/{mareaId}/add-crew', [App\Http\Controllers\MareaController::class, 'addCrew'])->name('panel.mareas.add-crew');
    Route::delete('/mareas/{mareaId}/remove-crew/{crewMember}', [App\Http\Controllers\MareaController::class, 'removeCrew'])->name('panel.mareas.remove-crew');
    Route::post('/mareas/{mareaId}/add-quantity-return', [App\Http\Controllers\MareaController::class, 'addQuantityReturn'])->name('panel.mareas.add-quantity-return');
    Route::delete('/mareas/{mareaId}/remove-quantity-return/{quantityReturn}', [App\Http\Controllers\MareaController::class, 'removeQuantityReturn'])->name('panel.mareas.remove-quantity-return');

    // Marea API Endpoints
    Route::get('/mareas/{mareaId}/available-movimentations', [App\Http\Controllers\MareaController::class, 'getAvailableTransactions'])->name('panel.mareas.available-movimentations');
    Route::get('/mareas/{mareaId}/available-crew', [App\Http\Controllers\MareaController::class, 'getAvailableCrew'])->name('panel.mareas.available-crew');
    Route::get('/mareas/{mareaId}/crew-salary-data', [App\Http\Controllers\MareaController::class, 'getCrewSalaryData'])->name('panel.mareas.crew-salary-data');
    Route::post('/mareas/{mareaId}/salary-payment', [App\Http\Controllers\MareaController::class, 'createSalaryPayment'])->name('panel.mareas.salary-payment');

    // Marea Distribution Items (for custom overrides)
    Route::post('/mareas/{mareaId}/distribution-items', [App\Http\Controllers\MareaController::class, 'storeDistributionItems'])->name('panel.mareas.distribution-items.store');

    // Maintenances (scoped to current vessel)
    Route::get('/maintenances', [App\Http\Controllers\MaintenanceController::class, 'index'])->name('panel.maintenances.index');
    Route::get('/maintenances/create', [App\Http\Controllers\MaintenanceController::class, 'create'])->name('panel.maintenances.create');
    Route::post('/maintenances', [App\Http\Controllers\MaintenanceController::class, 'store'])->name('panel.maintenances.store');
    Route::get('/maintenances/{maintenanceId}', [App\Http\Controllers\MaintenanceController::class, 'show'])->name('panel.maintenances.show');
    Route::put('/maintenances/{maintenanceId}', [App\Http\Controllers\MaintenanceController::class, 'update'])->name('panel.maintenances.update');
    Route::post('/maintenances/{maintenanceId}/finalize', [App\Http\Controllers\MaintenanceController::class, 'finalize'])->name('panel.maintenances.finalize');
    Route::delete('/maintenances/{maintenanceId}', [App\Http\Controllers\MaintenanceController::class, 'destroy'])->name('panel.maintenances.destroy');
    Route::delete('/maintenances/{maintenanceId}/remove-movimentation/{transaction}', [App\Http\Controllers\MaintenanceController::class, 'removeTransaction'])->name('panel.maintenances.remove-movimentation');

    // Marea Distribution Profiles (global, but vessel-scoped for consistency)
    Route::get('/marea-distribution-profiles', [App\Http\Controllers\MareaDistributionProfileController::class, 'index'])->name('panel.marea-distribution-profiles.index');
    Route::get('/marea-distribution-profiles/create', [App\Http\Controllers\MareaDistributionProfileController::class, 'create'])->name('panel.marea-distribution-profiles.create');
    Route::post('/marea-distribution-profiles', [App\Http\Controllers\MareaDistributionProfileController::class, 'store'])->name('panel.marea-distribution-profiles.store');
    Route::get('/marea-distribution-profiles/{id}', [App\Http\Controllers\MareaDistributionProfileController::class, 'show'])->name('panel.marea-distribution-profiles.show');
    Route::get('/marea-distribution-profiles/{id}/edit', [App\Http\Controllers\MareaDistributionProfileController::class, 'edit'])->name('panel.marea-distribution-profiles.edit');
    Route::put('/marea-distribution-profiles/{id}', [App\Http\Controllers\MareaDistributionProfileController::class, 'update'])->name('panel.marea-distribution-profiles.update');
    Route::delete('/marea-distribution-profiles/{id}', [App\Http\Controllers\MareaDistributionProfileController::class, 'destroy'])->name('panel.marea-distribution-profiles.destroy');

    // Recycle Bin
    Route::get('/recycle-bin', [App\Http\Controllers\RecycleBinController::class, 'index'])->name('panel.recycle-bin.index');
    Route::post('/recycle-bin/{type}/{id}/restore', [App\Http\Controllers\RecycleBinController::class, 'restore'])->name('panel.recycle-bin.restore');
    Route::delete('/recycle-bin/{type}/{id}', [App\Http\Controllers\RecycleBinController::class, 'destroy'])->name('panel.recycle-bin.destroy');
    Route::post('/recycle-bin/empty', [App\Http\Controllers\RecycleBinController::class, 'empty'])->name('panel.recycle-bin.empty');

    // Financial Reports (scoped to current vessel)
    Route::get('/financial-reports', [FinancialReportController::class, 'index'])->name('panel.financial-reports.index');
    Route::get('/financial-reports/{year}/{month}', [FinancialReportController::class, 'show'])
        ->where(['year' => '[0-9]{4}', 'month' => '[0-9]{1,2}'])
        ->name('panel.financial-reports.show');

    // VAT Reports (scoped to current vessel)
    Route::get('/vat-reports', [VatReportController::class, 'index'])->name('panel.vat-reports.index');
    Route::get('/vat-reports/{year}/{month}', [VatReportController::class, 'show'])
        ->where(['year' => '[0-9]{4}', 'month' => '[0-9]{1,2}'])
        ->name('panel.vat-reports.show');

    // Auditory (monitoring) - Only for administrators
    Route::middleware('role:admin,administrator')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('panel.audit-logs.index');
        Route::get('/audit-logs/recent', [AuditLogController::class, 'recent'])->name('panel.audit-logs.recent');
    });
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
