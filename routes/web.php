<?php

use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin Routes
Route::middleware(['auth', 'role:super-admin'])
    ->prefix('dashboard')
    ->name('dashboard.')
    ->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('categories', CategoryController::class);
});

// ALl user routes
Route::middleware('auth')
    ->prefix('dashboard')
    ->name('dashboard.')
    ->group(function () {
        // Document Routes
        Route::resource('documents', DocumentController::class);

        // Sign
        Route::get('documents/{document}/sign', [DocumentController::class, 'sign'])
            ->name('documents.sign');
        Route::post('documents/{document}/sign', [DocumentController::class, 'signStore'])
            ->name('documents.sign.store');
        Route::post('documents/{document}/revisi', [DocumentController::class, 'revisiStore'])
            ->name('documents.revisi.store');
        Route::resource('archiveds', ArchiveController::class);

        // Stamp
        Route::get('documents/{document}/stamp', [DocumentController::class, 'stamp'])
            ->name('documents.stamp');
        Route::post('documents/{document}/stamp', [DocumentController::class, 'stampStore'])
            ->name('documents.stamp.store');
});

// Staff Route
Route::middleware(['auth', 'role:super-admin|staff|staff-haul'])
    ->prefix('dashboard')
    ->name('dashboard.')
    ->group(function () {
        Route::get('documents/create', [DocumentController::class, 'create'])->name('documents.create');
});

require __DIR__.'/auth.php';
