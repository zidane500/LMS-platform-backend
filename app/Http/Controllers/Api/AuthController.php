<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;      
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    // ─── REGISTER ─────────────────────────────────────────
    public function register(Request $request)
    {
        $validated = $request->validate([
            'prenom'          => 'required|string|max:100',
            'nom'             => 'required|string|max:100',
            'email'           => 'required|email|unique:users,email',
            'mot_de_passe'    => ['required', 'confirmed',
                                   PasswordRule::min(8)->mixedCase()->numbers()],
            'telephone'       => 'nullable|string|max:20',
            'date_naissance'  => 'nullable|date',
            'langue_preferee' => 'nullable|string|max:10',
            'domaines_cibles' => 'nullable|array',
            'technologies'    => 'nullable|array',
        ]);

        $user = User::create([
            'prenom'          => $validated['prenom'],
            'nom'             => $validated['nom'],
            'email'           => $validated['email'],
            'mot_de_passe'    => Hash::make($validated['mot_de_passe']),
            'telephone'       => $validated['telephone'] ?? null,
            'date_naissance'  => $validated['date_naissance'] ?? null,
            'langue_preferee' => $validated['langue_preferee'] ?? 'fr',
            'role'            => 'apprenant',
        ]);

        Apprenant::create([
            'user_id'         => $user->id,
            'domaines_cibles' => $validated['domaines_cibles'] ?? [],
            'technologies'    => $validated['technologies'] ?? [],
        ]);


          // Notifier les admins d'une nouvelle inscription
         \App\Services\NotificationService::notifyAdmins(
          "👤 Nouvelle inscription : {$user->prenom} {$user->nom} ({$user->email})",
          'info'
           );

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'    => $this->formatUser($user),
            'token'   => $token,
            'message' => 'Compte créé avec succès',
        ], 201);
    }

    // ─── LOGIN ────────────────────────────────────────────
   public function login(Request $request)
{
    $request->validate([
        'email'        => 'required|email',
        'mot_de_passe' => 'required|string',
    ]);

    // ✅ Vérification Cloudflare Turnstile
    $this->verifierTurnstile($request);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->mot_de_passe, $user->mot_de_passe)) {
        throw ValidationException::withMessages([
            'email' => ['Email ou mot de passe incorrect.'],
        ]);
    }

    $user->tokens()->delete();

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'user'  => $this->formatUser($user),
        'token' => $token,
    ]);
}

    // ─── LOGOUT ───────────────────────────────────────────
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnecté avec succès',
        ]);
    }

    // ─── ME ───────────────────────────────────────────────
    public function me(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json([
            'message' => 'Unauthenticated'
        ], 401);
    }

    return response()->json($this->formatUser($user));
}

    // ─── MOT DE PASSE OUBLIÉ ──────────────────────────────
    // Envoie un email avec un lien de réinitialisation
   public function forgotPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
    ]);

    // ✅ Envoie l'email via le Password Broker Laravel + SMTP configuré
    $status = Password::sendResetLink(
        $request->only('email')
    );

    if ($status === Password::RESET_LINK_SENT) {
        return response()->json([
            'message' => 'Un email de réinitialisation a été envoyé.',
        ]);
    }

    return response()->json([
        'message' => 'Impossible d\'envoyer l\'email. Réessayez.',
    ], 400);
}

    // ─── RÉINITIALISER LE MOT DE PASSE ────────────────────
    // Reçoit le token + nouveau mot de passe
    // ─── RÉINITIALISER LE MOT DE PASSE ────────────────────
public function resetPassword(Request $request)
{
    $request->validate([
        'token'                     => 'required|string',
        'email'                     => 'required|email',
        'mot_de_passe'              => ['required', 'confirmed',
                                        PasswordRule::min(8)->mixedCase()->numbers()],
        'mot_de_passe_confirmation' => 'required',
    ]);

    $status = Password::reset(
        [
            'email'                 => $request->email,
            'token'                 => $request->token,
            'password'              => $request->mot_de_passe,
            'password_confirmation' => $request->mot_de_passe_confirmation,
        ],
        function (User $user, string $password) {
            // ✅ Mise à jour directe sans forceFill
            $user->mot_de_passe   = Hash::make($password);
            $user->remember_token = Str::random(60);
            $user->saveQuietly(); // ✅ saveQuietly évite les conflits d'events

            event(new PasswordReset($user));
        }
    );

    if ($status === Password::PASSWORD_RESET) {
        return response()->json([
            'message' => 'Mot de passe réinitialisé avec succès.',
        ]);
    }

    return response()->json([
        'message' => match($status) {
            Password::INVALID_TOKEN   => 'Token invalide ou expiré. Redemandez un email.',
            Password::INVALID_USER    => 'Utilisateur introuvable.',
            Password::RESET_THROTTLED => 'Trop de tentatives. Attendez quelques secondes.',
            default                   => 'Erreur inconnue : ' . $status,
        },
    ], 400);
}

private function verifierTurnstile(Request $request): void
{
    $token  = $request->input('cf_turnstile_response');
    $secret = env('CLOUDFLARE_TURNSTILE_SECRET');

    // En local seulement : autoriser le bypass pour les tests
    if (app()->environment('local') && (!$token || $token === 'bypass')) {
        return;
    }

    // Si le secret Cloudflare n'existe pas
    if (!$secret) {
        if (app()->environment('local')) {
            Log::warning('Cloudflare Turnstile secret manquant — bypass en local.');
            return;
        }

        abort(422, 'Configuration Turnstile manquante.');
    }

    // Si aucun token n'est envoyé
    if (!$token) {
        abort(422, 'Vérification de sécurité manquante.');
    }

    try {
        $http = Http::timeout(10)->asForm();

        // En local seulement : éviter l'erreur cURL error 60
        if (app()->environment('local')) {
            $http = $http->withoutVerifying();
        }

        $response = $http->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $request->ip(),
        ]);

        $result = $response->json();

        Log::info('Cloudflare Turnstile response', [
            'status' => $response->status(),
            'success' => $result['success'] ?? false,
            'error_codes' => $result['error-codes'] ?? [],
        ]);

        if (!($result['success'] ?? false)) {
            abort(422, 'Vérification de sécurité échouée. Réessayez.');
        }
    } catch (\Throwable $e) {
        Log::warning('Cloudflare Turnstile error: ' . $e->getMessage());

        // En local seulement : ne pas bloquer les tests
        if (app()->environment('local')) {
            return;
        }

        abort(422, 'Vérification de sécurité indisponible.');
    }
}


    // ─── FORMAT USER (méthode privée) ─────────────────────
    private function formatUser(User $user): array
    {
        $apprenant = $user->apprenant;

        return [
            'id'              => (string) $user->id,
            'prenom'          => $user->prenom,
            'nom'             => $user->nom,
            'email'           => $user->email,
            'role'            => $user->role,
            'telephone'       => $user->telephone,
            'date_naissance'  => $user->date_naissance?->format('Y-m-d'),
            'photo_profil' => $user->photo_profil
            ? asset('storage/' . $user->photo_profil)
            : null,
            'langue_preferee' => $user->langue_preferee,
            'domaines_cibles' => $apprenant?->domaines_cibles ?? [],
            'technologies'    => $apprenant?->technologies ?? [],
            'peut_coder'      => (bool) ($user->peut_coder ?? false),
            'created_at'      => $user->created_at?->toISOString(),
        ];
    }
}
