<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Student and Supervisor Registration Routes
Route::get('student/register', [StudentController::class, 'showRegisterForm'])
    ->middleware('guest')
    ->name('student.register');
Route::post('student/register', [StudentController::class, 'register'])
    ->middleware('guest');
Route::get('supervisor/register', [SupervisorController::class, 'showRegisterForm'])
    ->middleware('guest')
    ->name('supervisor.register');
Route::post('supervisor/register', [SupervisorController::class, 'register'])
    ->middleware('guest');

// Dashboard Routes
Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('student/dashboard', 'student-dashboard')
    ->middleware(['auth', 'verified', 'role:student'])
    ->name('student.dashboard');

Route::view('supervisor/dashboard', 'supervisor-dashboard')
    ->middleware(['auth', 'verified', 'role:supervisor'])
    ->name('supervisor.dashboard');

Route::view('superadmin/dashboard', 'superadmin-dashboard')
    ->middleware(['auth', 'verified', 'role:superadmin'])
    ->name('superadmin.dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

// Admin routes
Route::middleware(['auth', 'role:superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Volt::route('roles', 'admin.role-management')->name('roles');
    Volt::route('accounts', 'admin.account-management')->name('accounts');
    Volt::route('supervisors', 'admin.supervisor-management')->name('supervisors');
    Volt::route('departments', 'admin.department-management')->name('departments');
    Volt::route('departments/{id}', 'admin.department-detail')->name('department.detail');
});
