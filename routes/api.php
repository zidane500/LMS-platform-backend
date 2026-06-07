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
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\CallController;
use App\Http\Controllers\Api\TwoFactorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ═══════════════════════════════════════════════════════════
//  ROUTES PUBLIQUES — accessibles sans token
// ═══════════════════════════════════════════════════════════

// ── Authentification ──────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register',        [AuthController::class, 'register'])->middleware('throttle:3,1');
    Route::post('/login',           [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
    Route::post('/reset-password',  [AuthController::class, 'resetPassword'])->middleware('throttle:3,1');
});

// ── Formations publiques ──────────────────────────────────
Route::get('/formations',                                          [FormationController::class, 'index']);
Route::get('/formations/categories',                               [FormationController::class, 'categories']);
Route::get('/formations/instructors',                              [FormationController::class, 'instructors']);
Route::get('/formations/{id}',                                     [FormationController::class, 'show']);
Route::get('/formations/{id}/feedbacks',                           [FeedbackController::class, 'index']);
Route::get('/formations/{formationId}/modules/{moduleId}/contenus',[ContenuController::class, 'index']);

// ── Certificats publics ───────────────────────────────────
Route::get('/certificats/verifier/{numero}', [CertificatController::class, 'verifier']);

// ── Streaming vidéo ───────────────────────────────────────
Route::get('/stream/{path}', function (Request $request, string $path) {
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

    if ($request->hasHeader('Range')) {
        preg_match('/bytes=(\d+)-(\d*)/', $request->header('Range'), $matches);
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

// ═══════════════════════════════════════════════════════════
//  ROUTES PROTÉGÉES — token Sanctum obligatoire
// ═══════════════════════════════════════════════════════════
Route::middleware('auth:sanctum')->group(function () {

    // ── Auth ─────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);
    });

    // ── 2FA (admins uniquement) ───────────────────────────
    Route::prefix('2fa')->group(function () {
        Route::get('/status',  [TwoFactorController::class, 'status']);
        Route::post('/setup',  [TwoFactorController::class, 'setup']);
        Route::post('/enable', [TwoFactorController::class, 'enable']);
        Route::post('/disable',[TwoFactorController::class, 'disable']);
    });

    // ── Profil utilisateur ────────────────────────────────
    Route::post('/users/profile', [UserController::class, 'updateProfile']);

    // ── Administration utilisateurs ───────────────────────
    Route::prefix('admin/users')->group(function () {
        Route::get('/',        [UserController::class, 'index']);
        Route::put('/{id}',    [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });
    Route::post('/admin/users/{id}/toggle-peut-coder', [UserController::class, 'togglePeutCoder']);

    // ── Demandes formateur ────────────────────────────────
    Route::prefix('instructor-requests')->group(function () {
        Route::get('/',              [InstructorRequestController::class, 'index']);
        Route::post('/',             [InstructorRequestController::class, 'store']);
        Route::get('/my',            [InstructorRequestController::class, 'myRequest']);
        Route::get('/my-request',    [InstructorRequestController::class, 'myRequest']);
        Route::post('/{id}/process', [InstructorRequestController::class, 'process']);
        Route::get('/{id}/file/{type}', [InstructorRequestController::class, 'downloadFile']);
        Route::delete('/{id}',       [InstructorRequestController::class, 'destroy']);
    });

    // ── Formations (CRUD) ─────────────────────────────────
    Route::prefix('formations')->group(function () {
        Route::post('/',            [FormationController::class, 'store']);
        Route::put('/{id}',         [FormationController::class, 'update']);
        Route::delete('/{id}',      [FormationController::class, 'destroy']);
        Route::post('/{id}/enroll', [FormationController::class, 'enroll']);
        Route::post('/{id}/verifier-code', [FormationController::class, 'verifierCode']);
        Route::get('/{id}/verifier-acces', [FormationController::class, 'verifierAcces']);
    });

    // ── Modules ───────────────────────────────────────────
    Route::prefix('formations/{formationId}/modules')->group(function () {
        Route::post('/',             [ModuleController::class, 'store']);
        Route::put('/{moduleId}',    [ModuleController::class, 'update']);
        Route::delete('/{moduleId}', [ModuleController::class, 'destroy']);
        Route::post('/reorder',      [ModuleController::class, 'reorder']);
    });

    // ── Contenus ──────────────────────────────────────────
    Route::prefix('formations/{formationId}/modules/{moduleId}/contenus')->group(function () {
        Route::post('/',                      [ContenuController::class, 'store']);
        Route::post('/{contenuId}',           [ContenuController::class, 'update']);
        Route::delete('/{contenuId}',         [ContenuController::class, 'destroy']);
        Route::post('/{contenuId}/consulter', [ContenuController::class, 'marquerConsulte']);
    });

    // ── Quiz ──────────────────────────────────────────────
    Route::prefix('formations/{formationId}/modules/{moduleId}/quiz')->group(function () {
        Route::get('/',                 [QuizController::class, 'show']);
        Route::post('/',                [QuizController::class, 'store']);
        Route::put('/{quizId}',         [QuizController::class, 'update']);
        Route::delete('/{quizId}',      [QuizController::class, 'destroy']);
        Route::post('/{quizId}/passer', [QuizController::class, 'passer']);
    });

    // ── Progression & Badges ──────────────────────────────
    Route::prefix('progression')->group(function () {
        Route::get('/badges-progress', [ProgressionController::class, 'badgeProgression']);
        Route::get('/',                [ProgressionController::class, 'index']);
        Route::get('/{formationId}',   [ProgressionController::class, 'show']);
        Route::get('/{formationId}/formateur', [ProgressionController::class, 'formateur']);
    });

    // ── Certificats ───────────────────────────────────────
    Route::prefix('certificats')->group(function () {
        Route::get('/',               [CertificatController::class, 'index']);
        Route::post('/{formationId}', [CertificatController::class, 'generer']);
    });

    // ── Notifications ─────────────────────────────────────
    Route::prefix('notifications')->group(function () {
        Route::get('/',           [NotificationController::class, 'index']);
        Route::get('/non-lues',   [NotificationController::class, 'nonLues']);
        Route::post('/tout-lire', [NotificationController::class, 'marquerToutLu']);
        Route::post('/{id}/lire', [NotificationController::class, 'marquerLu']);
        Route::delete('/{id}',    [NotificationController::class, 'destroy']);
    });

    // ── Dashboard ─────────────────────────────────────────
    Route::prefix('dashboard')->group(function () {
        Route::get('/mes-formations',           [DashboardController::class, 'mesFormations']);
        Route::get('/inscriptions-semaine',     [DashboardController::class, 'inscriptionsParSemaine']);
        Route::get('/apprenant/stats',          [DashboardController::class, 'apprenantStats']);
        Route::get('/users-stats',              [DashboardController::class, 'usersStats']);
        Route::get('/top-formations',           [DashboardController::class, 'topFormations']);
        Route::get('/temps-apprentissage',      [DashboardController::class, 'tempsApprentissage']);
        Route::get('/formations-attention',     [DashboardController::class, 'formationsAttention']);
        Route::get('/ia-stats',                 [DashboardController::class, 'iaStats']);
        Route::get('/certifications-stats',     [DashboardController::class, 'certificationsStats']);
        Route::get('/progression-par-categorie',[DashboardController::class, 'progressionParCategorie']);
        Route::get('/certifications-detaillees',[DashboardController::class, 'certificationsDetaillees']);
    });
    Route::post('/formations/{id}/temps', [DashboardController::class, 'enregistrerTemps']);

    // ── Messages ──────────────────────────────────────────
    Route::get('/messages/inbox',                      [MessageController::class, 'inbox']);
    Route::post('/messages/{id}/react',                [MessageController::class, 'react']);
    Route::delete('/messages/{id}',                    [MessageController::class, 'destroy']);
    Route::get('/formations/{id}/messages',            [MessageController::class, 'index']);
    Route::post('/formations/{id}/messages',           [MessageController::class, 'store']);
    Route::post('/formations/{id}/messages/block',     [MessageController::class, 'blockUser']);
    Route::delete('/formations/{id}/messages/unblock', [MessageController::class, 'unblockUser']);

    // ── Feedbacks ─────────────────────────────────────────
    Route::post('/formations/{id}/feedbacks',             [FeedbackController::class, 'store']);
    Route::get('/formations/{id}/feedbacks/mon-feedback', [FeedbackController::class, 'monFeedback']);
    Route::delete('/feedbacks/{id}',                      [FeedbackController::class, 'destroy']);
    Route::put('/feedbacks/{id}',                         [FeedbackController::class, 'update']);
    Route::put('/feedbacks/{id}/repondre',                [FeedbackController::class, 'repondre']);

    // ── Reports ───────────────────────────────────────────
    Route::post('/reports', [ReportController::class, 'store']);

    // ── WebRTC Signaling ──────────────────────────────────
    Route::prefix('calls')->group(function () {
        Route::post('/voice-offer',   [CallController::class, 'voiceOffer']);
        Route::post('/video-offer',   [CallController::class, 'videoOffer']);
        Route::post('/answer',        [CallController::class, 'answer']);
        Route::post('/ice-candidate', [CallController::class, 'iceCandidate']);
        Route::post('/end',           [CallController::class, 'endCall']);
        Route::post('/reject',        [CallController::class, 'rejectCall']);
    });

    // ── GLM Correction (dev uniquement) ───────────────────
    if (app()->environment('local')) {
        Route::post('/test-glm-correction', function (Request $request, GlmCorrectionService $glm) {
            $data = $request->validate([
                'question'            => ['required', 'string'],
                'reponse'             => ['required', 'string'],
                'correction_attendue' => ['nullable', 'string'],
                'contexte'            => ['nullable', 'string'],
                'points_max'          => ['nullable', 'integer'],
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
    }
});