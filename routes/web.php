<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\SchoolManagementController;
use Illuminate\Support\Facades\Route;

Route::view('/dashboard', 'dashboard')
    ->middleware('auth')
    ->name('dashboard');

Route::view('/admin', 'dashboard')
    ->middleware(['auth', 'role:admin'])
    ->name('admin.dashboard');

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

Route::get('/', function () {
    return auth()->check()
        ? to_route('dashboard')
        : to_route('login');
});
