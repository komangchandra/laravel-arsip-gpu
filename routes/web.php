<?php

use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentFileController;
use App\Http\Controllers\DocumentSignController;
use App\Http\Controllers\DocumentSignRouteController;
use App\Http\Controllers\FullSignController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RevisionController;
use App\Http\Controllers\SigningInboxController;
use App\Http\Controllers\StampedController;
use App\Http\Controllers\UploadedController;
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
        Route::resource('documents', DocumentController::class)
            ->only(['index', 'store', 'edit', 'update', 'destroy']);

        Route::get('documents/{document}/sign-routing', [DocumentSignRouteController::class, 'edit'])
            ->name('documents.sign-routing.edit');
        Route::put('documents/{document}/sign-routing', [DocumentSignRouteController::class, 'update'])
            ->name('documents.sign-routing.update');
        Route::post('documents/{document}/sign-routing/start', [DocumentSignRouteController::class, 'start'])
            ->name('documents.sign-routing.start');
        Route::post('documents/{document}/sign-routing/cancel', [DocumentSignRouteController::class, 'cancel'])
            ->name('documents.sign-routing.cancel');

        Route::get('documents/{document}/sign', [DocumentSignController::class, 'show'])
            ->name('documents.sign');
        Route::post('documents/{document}/sign', [DocumentSignController::class, 'store'])
            ->middleware('throttle:sign-document')
            ->name('documents.sign.store');
        Route::get('documents/{document}/sign-tempel', [DocumentSignController::class, 'showStamp'])
            ->name('documents.sign-tempel');
        Route::post('documents/{document}/sign-tempel', [DocumentSignController::class, 'storeStamp'])
            ->middleware('throttle:sign-document')
            ->name('documents.sign-tempel.store');
        Route::post('documents/{document}/request-revision', [DocumentSignController::class, 'requestRevision'])
            ->name('documents.sign.request-revision');
        Route::get('signing-inbox', [SigningInboxController::class, 'index'])
            ->name('signing-inbox.index');
        Route::resource('archiveds', ArchiveController::class)->only('index');

        // Stamp
        Route::get('documents/{document}/stamp', [DocumentController::class, 'stamp'])
            ->name('documents.stamp');
        Route::post('documents/{document}/stamp', [DocumentController::class, 'stampStore'])
            ->name('documents.stamp.store');
        Route::patch('documents/{document}/archive', [DocumentController::class, 'archive'])
            ->name('documents.archive');

        // Download
        Route::get('documents/{document}/preview', [DocumentFileController::class, 'preview'])
            ->name('documents.preview');
        Route::get('documents/{document}/download', [DocumentFileController::class, 'download'])
            ->name('documents.download');
        Route::get('documents/{document}/signature-assets/{asset}', [DocumentFileController::class, 'signatureAsset'])
            ->where('asset', '[A-Za-z0-9._-]+')
            ->name('documents.signature-assets.show');

        // =========================
        // REVISIONS (DIPISAH)
        // =========================
        Route::get('revisions', [RevisionController::class, 'index'])
            ->name('revisions.index');

        // =========================
        // FULL SIGN (DIPISAH)
        // =========================
        Route::get('full-sign', [FullSignController::class, 'index'])
            ->name('full-sign.index');

        // =========================
        // RECENTLY U (DIPISAH)
        // =========================
        Route::get('recently-uploaded', [UploadedController::class, 'index'])
            ->name('recently-uploaded.index');

        // =========================
        // STAMPED (DIPISAH)
        // =========================
        Route::get('stamped', [StampedController::class, 'index'])
            ->name('stamped.index');

        Route::get('documents/{document}/annotate', [DocumentController::class, 'annotate'])
            ->name('documents.annotate');
        Route::post('documents/{document}/annotate-upload', [DocumentController::class, 'annotateUpload'])
            ->name('documents.annotateUpload');
    });

// Staff Route
Route::middleware(['auth', 'role:super-admin|staff|staff-haul'])
    ->prefix('dashboard')
    ->name('dashboard.')
    ->group(function () {
        Route::get('documents/create', [DocumentController::class, 'create'])
            ->middleware('can:create,App\\Models\\Document')
            ->name('documents.create');
    });

require __DIR__.'/auth.php';
