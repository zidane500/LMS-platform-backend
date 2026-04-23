<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\InstructorRequestController;
use App\Http\Controllers\Api\FormationController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\ContenuController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\ProgressionController;
use App\Services\GlmCorrectionService;
use App\Http\Controllers\Api\CertificatController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Http\Request;
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

Route::get('/certificats/verifier/{numero}', [CertificatController::class, 'verifier']);




    

// ═══════════════════════════════════════════════════════════
//  ROUTES PROTÉGÉES
// ═══════════════════════════════════════════════════════════
Route::middleware('auth:sanctum')->group(function () {

Route::post('/reports', [ReportController::class, 'store']);

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

    // ── EPIC 4 : Quiz ──────────────────────────────────────
    Route::prefix('formations/{formationId}/modules/{moduleId}/quiz')->group(function () {
        Route::get('/',              [QuizController::class, 'show']);       // Récupérer
        Route::post('/',             [QuizController::class, 'store']);      // Créer
        Route::put('/{quizId}',      [QuizController::class, 'update']);     // Modifier
        Route::delete('/{quizId}',   [QuizController::class, 'destroy']);    // Supprimer
        Route::post('/{quizId}/passer', [QuizController::class, 'passer']); // Passer
        
    });

    // EPIC 5 : Progression et Badges
    Route::prefix('progression')->group(function () {
     Route::get('/progression/badges-progress', [ProgressionController::class, 'badgeProgression']);
    Route::get('/',                        [ProgressionController::class, 'index']);
    Route::get('/{formationId}',           [ProgressionController::class, 'show']);
    Route::get('/{formationId}/formateur', [ProgressionController::class, 'formateur']);
   
    
});

// Epic 6 : certification
    Route::prefix('certificats')->group(function () {
    Route::get('/',                    [CertificatController::class, 'index']);
    Route::post('/{formationId}',      [CertificatController::class, 'generer']);
});

// Notification
Route::prefix('notifications')->group(function () {
    Route::get('/',              [NotificationController::class, 'index']);
    Route::get('/non-lues',      [NotificationController::class, 'nonLues']);
    Route::post('/tout-lire',    [NotificationController::class, 'marquerToutLu']);
    Route::post('/{id}/lire',    [NotificationController::class, 'marquerLu']);
    Route::delete('/{id}',       [NotificationController::class, 'destroy']);
});


// ── Dashboard Charts ──────────────────────────────────────
Route::prefix('dashboard')->group(function () {
    Route::get('/mes-formations',          [DashboardController::class, 'mesFormations']);
    Route::get('/inscriptions-semaine',    [DashboardController::class, 'inscriptionsParSemaine']);
    Route::get('/apprenant/stats',         [DashboardController::class, 'apprenantStats']);
});

// ── Demandes formateur ────────────────────────────────────
Route::get('/instructor-requests',             [InstructorRequestController::class, 'index']);
Route::post('/instructor-requests',            [InstructorRequestController::class, 'store']);
Route::get('/instructor-requests/my-request',  [InstructorRequestController::class, 'myRequest']);
Route::post('/instructor-requests/{id}/process', [InstructorRequestController::class, 'process']);
Route::delete('/instructor-requests/{id}',     [InstructorRequestController::class, 'destroy']);


});



Route::post('/test-glm-correction', function (Request $request, GlmCorrectionService $glm) {
    $data = $request->validate([
        'question' => ['required', 'string'],
        'reponse' => ['required', 'string'],
        'correction_attendue' => ['nullable', 'string'],
        'contexte' => ['nullable', 'string'],
        'points_max' => ['nullable', 'integer'],
    ]);

    return response()->json(
        $glm->corrigerReponseLibre(
            $data['question'],
            $data['reponse'],
            $data['correction_attendue'] ?? null,
            $data['contexte'] ?? '',
            $data['points_max'] ?? 10
        )
    );
});


Route::get('/test-ollama-config', function () {
    return response()->json([
        'base_url' => config('services.ollama.base_url'),
        'model' => config('services.ollama.model'),
        'has_api_key' => !empty(config('services.ollama.api_key')),
        'api_key_prefix' => substr(config('services.ollama.api_key'), 0, 8),
    ]);
});


Route::get('/certificats/verifier/{numero}', [CertificatController::class, 'verifier']);


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