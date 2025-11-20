<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route untuk otentikasi (tanpa middleware 'auth:sanctum')
Route::post('/login', [AuthController   ::class, 'login']);

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']); 
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::get('/documents/{document}/show', [DocumentController::class, 'show']);
    Route::post('/documents/{document}/sign', [DocumentController::class, 'sign']);
    // ... Route API Anda yang lain
});

// Route::middleware('auth:sanctum')->post('/documents/{document}/sign', [DocumentController::class, 'sign']);
// Route::middleware('auth:sanctum')->get('/documents/{document}/show', [DocumentController::class, 'view']);