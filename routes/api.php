<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\InstructorRequestController;
use App\Http\Controllers\Api\FormationController;
use App\Http\Controllers\Api\ModuleController;
use Illuminate\Support\Facades\Route;

// ═══════════════════════════════════════════════════════════
//  ROUTES PUBLIQUES (sans authentification)
// ═══════════════════════════════════════════════════════════
Route::prefix('auth')->group(function () {
    Route::post('/register',        [AuthController::class, 'register']);
    Route::post('/login',           [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
});

// Formations publiques (lecture seule, publiées)
Route::get('/formations',            [FormationController::class, 'index']);
Route::get('/formations/categories', [FormationController::class, 'categories']);
Route::get('/formations/{id}',       [FormationController::class, 'show']);

// ═══════════════════════════════════════════════════════════
//  ROUTES PROTÉGÉES (token Sanctum obligatoire)
// ═══════════════════════════════════════════════════════════
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);
    });

    // US 1.6 - Modifier son propre profil
    Route::post('/users/profile', [UserController::class, 'updateProfile']);

    // US 1.7 - Gestion admin des utilisateurs
    Route::prefix('admin/users')->group(function () {
        Route::get('/',        [UserController::class, 'index']);
        Route::put('/{id}',    [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    // US 1.4 + 1.5 - Demandes formateur
    Route::prefix('instructor-requests')->group(function () {
        Route::post('/',              [InstructorRequestController::class, 'store']);
        Route::get('/my',             [InstructorRequestController::class, 'myRequest']);
        Route::get('/',               [InstructorRequestController::class, 'index']);
        Route::post('/{id}/process',  [InstructorRequestController::class, 'process']);
        Route::get('/{id}/file/{type}', [InstructorRequestController::class, 'downloadFile']);
    });

    // US 2.1, 2.6 - CRUD formations (protégées)
    Route::prefix('formations')->group(function () {
        Route::post('/',               [FormationController::class, 'store']);
        Route::put('/{id}',            [FormationController::class, 'update']);
        Route::delete('/{id}',         [FormationController::class, 'destroy']);
        Route::post('/{id}/enroll',    [FormationController::class, 'enroll']);
    });

    // US 2.2, 2.5 - Modules
    Route::prefix('formations/{formationId}/modules')->group(function () {
        Route::post('/',              [ModuleController::class, 'store']);
        Route::put('/{moduleId}',     [ModuleController::class, 'update']);
        Route::delete('/{moduleId}',  [ModuleController::class, 'destroy']);
        Route::post('/reorder',       [ModuleController::class, 'reorder']);
    });
});
