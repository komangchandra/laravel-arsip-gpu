<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DocumentApprovalController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard.index');
})->middleware(['auth', 'verified'])->name('dashboard');

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
        Route::get('documents/{document}/sign', [DocumentController::class, 'sign'])
            ->name('documents.sign');

        Route::post('documents/{document}/sign', [DocumentController::class, 'signStore'])
            ->name('documents.sign.store');
});

// Route::get('/test-image', function () {
//     return \Intervention\Image\Laravel\Facades\Image::canvas(200, 200, '#ff0000')->toPngResponse();
// });


require __DIR__.'/auth.php';
