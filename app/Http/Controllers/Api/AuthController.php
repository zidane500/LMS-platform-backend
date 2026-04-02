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

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->mot_de_passe, $user->mot_de_passe)) {
            throw ValidationException::withMessages([
                'email' => ['Email ou mot de passe incorrect.'],
            ]);
        }

        // Révoquer les anciens tokens avant d'en créer un nouveau
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
        return response()->json($this->formatUser($request->user()));
    }

    // ─── MOT DE PASSE OUBLIÉ ──────────────────────────────
    // Envoie un email avec un lien de réinitialisation
    public function forgotPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
    ]);

    // Génère et sauvegarde le token dans password_reset_tokens
    $token = \Illuminate\Support\Str::random(64);

    DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $request->email],
        [
            'token'      => bcrypt($token),
            'created_at' => now(),
        ]
    );

    // En mode développement → le lien est dans storage/logs/laravel.log
    Log::info('=== RESET PASSWORD LINK ===');
    Log::info('http://localhost:5173/reset-password?token=' . $token . '&email=' . urlencode($request->email));
    Log::info('===========================');

    return response()->json([
        'message' => 'Un email de réinitialisation a été envoyé.',
    ]);
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
        ];
    }
}
