<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\InstructorRequestController;
use App\Http\Controllers\Api\FormationController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\ContenuController;
use Illuminate\Support\Facades\Route;

// ═══════════════════════════════════════════════════════════
//  ROUTES PUBLIQUES
// ═══════════════════════════════════════════════════════════
Route::prefix('auth')->group(function () {
    Route::post('/register',        [AuthController::class, 'register']);
    Route::post('/login',           [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
});

Route::get('/formations',            [FormationController::class, 'index']);
Route::get('/formations/categories', [FormationController::class, 'categories']);
Route::get('/formations/instructors', [FormationController::class, 'instructors']);
Route::get('/formations/{id}',       [FormationController::class, 'show']);


// Contenus publics (avec progression si connecté)
Route::get('/formations/{formationId}/modules/{moduleId}/contenus',
    [ContenuController::class, 'index']);

// ═══════════════════════════════════════════════════════════
//  ROUTES PROTÉGÉES
// ═══════════════════════════════════════════════════════════
Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);
    });

    Route::post('/users/profile', [UserController::class, 'updateProfile']);

    Route::prefix('admin/users')->group(function () {
        Route::get('/',        [UserController::class, 'index']);
        Route::put('/{id}',    [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    Route::prefix('instructor-requests')->group(function () {
        Route::post('/',                    [InstructorRequestController::class, 'store']);
        Route::get('/my',                   [InstructorRequestController::class, 'myRequest']);
        Route::get('/',                     [InstructorRequestController::class, 'index']);
        Route::post('/{id}/process',        [InstructorRequestController::class, 'process']);
        Route::get('/{id}/file/{type}',     [InstructorRequestController::class, 'downloadFile']);
    });

    Route::prefix('formations')->group(function () {
        Route::post('/',            [FormationController::class, 'store']);
        Route::put('/{id}',         [FormationController::class, 'update']);
        Route::delete('/{id}',      [FormationController::class, 'destroy']);
        Route::post('/{id}/enroll', [FormationController::class, 'enroll']);
    });

    Route::prefix('formations/{formationId}/modules')->group(function () {
        Route::post('/',             [ModuleController::class, 'store']);
        Route::put('/{moduleId}',    [ModuleController::class, 'update']);
        Route::delete('/{moduleId}', [ModuleController::class, 'destroy']);
        Route::post('/reorder',      [ModuleController::class, 'reorder']);
    });

    // ── US 3.1, 3.3, 3.4 — CRUD contenus ──────────────────
    Route::prefix('formations/{formationId}/modules/{moduleId}/contenus')->group(function () {
        Route::post('/',                      [ContenuController::class, 'store']);
        Route::post('/{contenuId}',            [ContenuController::class, 'update']);
        Route::delete('/{contenuId}',         [ContenuController::class, 'destroy']);
        // US 3.2 — Marquer consulté (apprenant)
        Route::post('/{contenuId}/consulter', [ContenuController::class, 'marquerConsulte']);
    });

});


    // ── Route de streaming vidéo avec Range support ──────────
Route::get('/stream/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . urldecode($path));
    
    if (!file_exists($fullPath)) {
        abort(404);
    }

    $mimeType = mime_content_type($fullPath);
    $fileSize = filesize($fullPath);
    $start    = 0;
    $end      = $fileSize - 1;

    $headers = [
        'Content-Type'   => $mimeType,
        'Accept-Ranges'  => 'bytes',
        'Content-Length' => $fileSize,
    ];

    // Gestion Range (seek vidéo)
    if (request()->hasHeader('Range')) {
        preg_match('/bytes=(\d+)-(\d*)/', request()->header('Range'), $matches);
        $start = (int) $matches[1];
        $end   = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;

        $headers['Content-Range']  = "bytes $start-$end/$fileSize";
        $headers['Content-Length'] = $end - $start + 1;

        return response()->stream(function () use ($fullPath, $start, $end) {
            $fp = fopen($fullPath, 'rb');
            fseek($fp, $start);
            $remaining = $end - $start + 1;
            while ($remaining > 0 && !feof($fp)) {
                $chunk = min(8192, $remaining);
                echo fread($fp, $chunk);
                $remaining -= $chunk;
                flush();
            }
            fclose($fp);
        }, 206, $headers);
    }

    return response()->stream(function () use ($fullPath) {
        readfile($fullPath);
    }, 200, $headers);
})->where('path', '.*');