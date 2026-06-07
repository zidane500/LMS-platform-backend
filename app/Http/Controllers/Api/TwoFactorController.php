<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Log;

class TwoFactorController extends Controller
{
    // ─── Générer le QR code pour l'admin ──────────────────
    public function setup(Request $request)
{
    $user = $request->user();

    if ($user->role !== 'admin') {
        return response()->json(['message' => 'Accès réservé aux admins.'], 403);
    }

    $google2fa = new Google2FA();

    // ── Ne regénère le secret QUE s'il n'en existe pas déjà un ──
    if (!$user->google2fa_secret) {
        $secret = $google2fa->generateSecretKey();
        $user->google2fa_secret  = $secret;
        $user->google2fa_enabled = false;
        $user->save();
    } else {
        $secret = $user->google2fa_secret;
    }

    $qrCodeUrl = $google2fa->getQRCodeUrl(
        config('app.name'),
        $user->email,
        $secret
    );

    return response()->json([
        'secret'      => $secret,
        'qr_code_url' => $qrCodeUrl,
    ]);
}

    // ─── Confirmer et activer la 2FA ──────────────────────
    public function enable(Request $request)
{
    $request->validate([
        'code' => 'required|string|size:6',
    ]);

    $user      = $request->user();
    $google2fa = new Google2FA();

    // ── Log pour déboguer ──────────────────────────────
    Log::info('2FA enable attempt', [
        'user_id'       => $user->id,
        'secret_stored' => $user->google2fa_secret,
        'code_received' => $request->code,
        'server_time'   => now()->toISOString(),
    ]);

    $valid = $google2fa->verifyKey(
        $user->google2fa_secret,
        $request->code,
        4
    );

    Log::info('2FA verify result', ['valid' => $valid]);

    if (!$valid) {
        return response()->json([
            'message' => 'Code invalide. Réessayez.',
        ], 422);
    }

    $user->google2fa_enabled = true;
    $user->save();

    return response()->json([
        'message' => '2FA activée avec succès.',
    ]);
}

    // ─── Désactiver la 2FA ────────────────────────────────
    public function disable(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user     = $request->user();
        $google2fa = new Google2FA();

        $valid = $google2fa->verifyKey(
            $user->google2fa_secret,
            $request->code,
            2
        );

        if (!$valid) {
            return response()->json([
                'message' => 'Code invalide.',
            ], 422);
        }

        $user->google2fa_secret  = null;
        $user->google2fa_enabled = false;
        $user->save();

        return response()->json([
            'message' => '2FA désactivée.',
        ]);
    }
}