<?php

use App\Http\Controllers\Admin\DemandSetupController;
use App\Http\Controllers\Admin\SchoolManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FieldStaff\DeliveryController;
use App\Http\Controllers\FieldStaff\DeliveryPhotoController;
use App\Http\Controllers\Reports\DailyDeliveryReportController;
use App\Http\Controllers\Reports\FormSevenReportController;
use App\Http\Controllers\Reports\FormSevenPdfController;
use App\Http\Controllers\Reports\FormFourPdfController;
use App\Http\Controllers\Reports\FormFourReportController;
use App\Http\Controllers\Reports\FormTenPdfController;
use App\Http\Controllers\Reports\FormTenReportController;
use App\Http\Controllers\Reports\OfficialReportController;
use App\Http\Controllers\Reports\StockFormController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', DashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

Route::get('/admin', DashboardController::class)
    ->middleware(['auth', 'role:admin'])
    ->name('admin.dashboard');

Route::middleware(['auth', 'role:admin,field_staff'])
    ->prefix('reports/daily-delivery')
    ->name('reports.daily-delivery.')
    ->group(function (): void {
        Route::get('/', [DailyDeliveryReportController::class, 'index'])->name('index');
        Route::get('/export', [DailyDeliveryReportController::class, 'export'])->name('export');
    });

Route::get('/admin/reports/form-7', FormSevenReportController::class)
    ->middleware(['auth', 'role:admin'])
    ->name('admin.reports.form-seven');

Route::get('/admin/reports/form-7/pdf', FormSevenPdfController::class)
    ->middleware(['auth', 'role:admin'])
    ->name('admin.reports.form-seven.pdf');

Route::get('/admin/reports/form-4', FormFourReportController::class)
    ->middleware(['auth', 'role:admin'])
    ->name('admin.reports.form-four');

Route::get('/admin/reports/form-4/pdf', FormFourPdfController::class)
    ->middleware(['auth', 'role:admin'])
    ->name('admin.reports.form-four.pdf');

Route::get('/admin/reports/form-10', FormTenReportController::class)
    ->middleware(['auth', 'role:admin'])
    ->name('admin.reports.form-ten');

Route::get('/admin/reports/form-10/pdf', FormTenPdfController::class)
    ->middleware(['auth', 'role:admin'])
    ->name('admin.reports.form-ten.pdf');

Route::middleware(['auth', 'role:admin'])->prefix('admin/reports/form-{form}')
    ->where(['form' => '12|13'])->name('admin.reports.stock.')->group(function (): void {
        Route::get('/', [StockFormController::class, 'information'])->name('information');
        Route::put('/information', [StockFormController::class, 'savePeriod'])->name('information.update');
        Route::put('/schools/{school}', [StockFormController::class, 'saveSchool'])->name('schools.update');
        Route::get('/preview', [StockFormController::class, 'preview'])->name('preview');
        Route::get('/pdf', [StockFormController::class, 'pdf'])->name('pdf');
    });

Route::get('/admin/reports', OfficialReportController::class)
    ->middleware(['auth', 'role:admin'])
    ->name('admin.reports.index');

Route::put('/admin/reports/period', [OfficialReportController::class, 'savePeriod'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.reports.period.update');

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

Route::middleware(['auth', 'role:field_staff'])->prefix('field-staff/deliveries')->name('field-staff.deliveries.')->group(function (): void {
    Route::get('/', [DeliveryController::class, 'index'])->name('index');
    Route::get('/create', [DeliveryController::class, 'create'])->name('create');
    Route::post('/', [DeliveryController::class, 'store'])->name('store');
    Route::get('/{delivery}/edit', [DeliveryController::class, 'edit'])->name('edit');
    Route::put('/{delivery}', [DeliveryController::class, 'update'])->name('update');
    Route::get('/{delivery}/chalan', [DeliveryPhotoController::class, 'show'])->name('chalan');
});

Route::get('/', function () {
    return auth()->check()
        ? to_route('dashboard')
        : to_route('login');
});
