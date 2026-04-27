<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ReportController extends Controller
{
    public function store(Request $request)
{
    $request->validate([
        'resume'     => 'required|string|max:100',       // ✅ NOUVEAU
        'message'    => 'required|string|min:5|max:1000',
        'fichiers'   => 'nullable|array|max:5',
        'fichiers.*' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,mkv',
    ]);

    $user    = $request->user();
    $nom     = $user->prenom . ' ' . $user->nom;
    $role    = ucfirst($user->role);
    $email   = $user->email;
    $resume  = $request->resume;
    $message = $request->message;

    // ── Gérer les fichiers uploadés ─────────────────────
    $fichiersUploades = [];
    if ($request->hasFile('fichiers')) {
        foreach ($request->file('fichiers') as $fichier) {
            $chemin = $fichier->store('signalements', 'public');
            $fichiersUploades[] = [
                'chemin' => storage_path('app/public/' . $chemin),
                'nom'    => $fichier->getClientOriginalName(),
                'mime'   => $fichier->getMimeType(),
            ];
        }
    }

    // ── Email à la plateforme ─────────────────────────────
    $platformEmail = config('mail.from.address');

    Mail::send([], [], function ($mail) use (
        $nom, $role, $email, $resume, $message, $fichiersUploades, $platformEmail
    ) {
        $mail->to($platformEmail)
             ->subject("Signalement sur la plateforme LMS")
             ->html("
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #dc2626;'>Nouveau signalement</h2>
                    <table style='width:100%; border-collapse:collapse;'>
                        <tr><td style='padding:8px; font-weight:bold; background:#f3f4f6;'>Nom</td>
                            <td style='padding:8px;'>{$nom}</td></tr>
                        <tr><td style='padding:8px; font-weight:bold; background:#f3f4f6;'>Rôle</td>
                            <td style='padding:8px;'>{$role}</td></tr>
                        <tr><td style='padding:8px; font-weight:bold; background:#f3f4f6;'>Email</td>
                            <td style='padding:8px;'><a href='mailto:{$email}'>{$email}</a></td></tr>
                        <tr><td style='padding:8px; font-weight:bold; background:#f3f4f6;'>Résumé</td>
                            <td style='padding:8px; font-weight:bold; color:#dc2626;'>{$resume}</td></tr>
                    </table>
                    <h3 style='margin-top:20px;'>Description détaillée :</h3>
                    <p style='background:#fef3c7; padding:15px; border-left:4px solid #f59e0b; border-radius:4px;'>
                        " . nl2br(htmlspecialchars($message)) . "
                    </p>
                    " . (count($fichiersUploades) > 0
                        ? "<p style='color:#6b7280;'>📎 " . count($fichiersUploades) . " fichier(s) joint(s)</p>"
                        : "") . "
                </div>
             ");

        foreach ($fichiersUploades as $f) {
            if (file_exists($f['chemin'])) {
                $mail->attach($f['chemin'], ['as' => $f['nom'], 'mime' => $f['mime']]);
            }
        }
    });

    // ✅ Notification admin avec le résumé visible directement
    NotificationService::notifyAdmins(
        "Signalement de {$nom} ({$role}) \nemail : {$email} \nMessage : {$resume}",
        'warning'
    );

    return response()->json(['message' => 'Votre signalement a été envoyé.']);
}
}