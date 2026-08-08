<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartementController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\SignalementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Nabd Al-Madina
|--------------------------------------------------------------------------
*/

// Public Authentication Routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Public Departements listing
Route::get('/departements', [DepartementController::class, 'index']);

// Protected Routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // User Profile & Logout
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Signalements Routes
    Route::get('/signalements', [SignalementController::class, 'index']);
    Route::post('/signalements', [SignalementController::class, 'store']);
    Route::get('/signalements/{signalement}', [SignalementController::class, 'show']);
    Route::patch('/signalements/{signalement}/statut', [SignalementController::class, 'updateStatus']);
    Route::patch('/signalements/{signalement}/departement', [SignalementController::class, 'assignDepartement']);
    Route::delete('/signalements/{signalement}', [SignalementController::class, 'destroy']);
    Route::get('/signalements/{signalement}/similaires', [SignalementController::class, 'similaires']);

    // Incidents Routes
    Route::get('/incidents', [IncidentController::class, 'index']);
    Route::post('/incidents', [IncidentController::class, 'store']);
    Route::post('/incidents/regrouper', [IncidentController::class, 'regrouper']);
    Route::get('/incidents/{incident}', [IncidentController::class, 'show']);
    Route::patch('/incidents/{incident}', [IncidentController::class, 'update']);
    Route::delete('/incidents/{incident}', [IncidentController::class, 'destroy']);

    // Departements detail
    Route::get('/departements/{departement}', [DepartementController::class, 'show']);
});
