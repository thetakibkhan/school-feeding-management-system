<?php

use App\Http\Controllers\Admin\DemandSetupController;
use App\Http\Controllers\Admin\SchoolManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Reports\FormSevenReportController;
use App\Http\Controllers\Reports\FormFourPdfController;
use App\Http\Controllers\Reports\FormFourReportController;
use Illuminate\Support\Facades\Route;

Route::view('/dashboard', 'dashboard')
    ->middleware('auth')
    ->name('dashboard');

Route::view('/admin', 'dashboard')
    ->middleware(['auth', 'role:admin'])
    ->name('admin.dashboard');

Route::get('/admin/reports/form-7', FormSevenReportController::class)
    ->middleware(['auth', 'role:admin'])
    ->name('admin.reports.form-seven');

Route::get('/admin/reports/form-4', FormFourReportController::class)
    ->middleware(['auth', 'role:admin'])
    ->name('admin.reports.form-four');

Route::get('/admin/reports/form-4/pdf', FormFourPdfController::class)
    ->middleware(['auth', 'role:admin'])
    ->name('admin.reports.form-four.pdf');

Route::middleware(['auth', 'role:admin'])->prefix('admin/users')->name('admin.users.')->group(function (): void {
    Route::get('/', [UserManagementController::class, 'index'])->name('index');
    Route::get('/create', [UserManagementController::class, 'create'])->name('create');
    Route::post('/', [UserManagementController::class, 'store'])->name('store');
    Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
    Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
    Route::patch('/{user}/deactivate', [UserManagementController::class, 'deactivate'])->name('deactivate');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/schools')->name('admin.schools.')->group(function (): void {
    Route::get('/', [SchoolManagementController::class, 'index'])->name('index');
    Route::post('/', [SchoolManagementController::class, 'store'])->name('store');
    Route::get('/{school}', [SchoolManagementController::class, 'show'])->name('show');
    Route::put('/{school}', [SchoolManagementController::class, 'update'])->name('update');
    Route::post('/{school}/student-counts', [SchoolManagementController::class, 'storeStudentCount'])->name('student-counts.store');
    Route::delete('/{school}', [SchoolManagementController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/demand-setup')->name('admin.demand-setup.')->group(function (): void {
    Route::get('/', [DemandSetupController::class, 'index'])->name('index');
    Route::put('/items/{foodItem}/specification', [DemandSetupController::class, 'updateItemSpecification'])->name('items.specification.update');
    Route::post('/schedules', [DemandSetupController::class, 'storeSchedule'])->name('schedules.store');
    Route::put('/schedules/{schedule}', [DemandSetupController::class, 'updateSchedule'])->name('schedules.update');
    Route::delete('/schedules/{schedule}', [DemandSetupController::class, 'destroySchedule'])->name('schedules.destroy');
    Route::post('/non-working-dates', [DemandSetupController::class, 'storeNonWorkingDate'])->name('non-working-dates.store');
    Route::put('/non-working-dates/{nonWorkingDate}', [DemandSetupController::class, 'updateNonWorkingDate'])->name('non-working-dates.update');
    Route::delete('/non-working-dates/{nonWorkingDate}', [DemandSetupController::class, 'destroyNonWorkingDate'])->name('non-working-dates.destroy');
});

Route::get('/', function () {
    return auth()->check()
        ? to_route('dashboard')
        : to_route('login');
});
