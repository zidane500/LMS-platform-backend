<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Inscription;
use App\Models\ProgressionContenu;
use App\Models\ProgressionFormation;
use App\Models\Notification;
use App\Models\Formation;
use App\Services\NotificationService;
use Carbon\Carbon;

class SendRappelNotifications extends Command
{
    protected $signature   = 'notifications:rappel';
    protected $description = 'Rappel aux apprenants/formateurs inactifs depuis 24h';

    public function handle(): void
    {
        $inscriptions = Inscription::with(['user', 'formation'])->get();

        foreach ($inscriptions as $inscription) {
            $user      = $inscription->user;
            $formation = $inscription->formation;

            if (!$user || !$formation) continue;
            if (!in_array($user->role, ['apprenant', 'formateur'])) continue;

            // Vérifier si formation déjà complète
            $progression = ProgressionFormation::where('user_id', $user->id)
                ->where('formation_id', $formation->id)
                ->first();

            if ($progression && $progression->complete) continue;

            // Dernière consultation de cette formation
            $tousContenusIds = $formation->modules()
                ->with('contenus')
                ->get()
                ->flatMap(fn($m) => $m->contenus->pluck('id'));

            $derniereConsultation = ProgressionContenu::where('user_id', $user->id)
                ->whereIn('contenu_id', $tousContenusIds)
                ->max('derniere_consultation');

            $limite = now()->subHours(24);

            // ✅ Si jamais consulté → pas de rappel
            // Le rappel ne se déclenche que si l'utilisateur a déjà consulté
            // au moins une fois et est inactif depuis plus de 24h
            if (is_null($derniereConsultation)) continue;

            // ✅ Vérifier que la dernière consultation dépasse 24h
            $inactifDepuis24h = Carbon::parse($derniereConsultation)->lessThan($limite);

            if (!$inactifDepuis24h) continue;

            // Vérifier qu'on n'a pas déjà envoyé un rappel dans les dernières 24h
            $dejaNotifie = Notification::where('user_id', $user->id)
                ->where('type', 'rappel')
                ->where('message', 'like', '%' . $formation->titre . '%')
                ->where('created_at', '>=', now()->subHours(24))
                ->exists();

            if ($dejaNotifie) continue;

            NotificationService::send(
                $user->id,
                "Vous n'avez pas consulté la formation \"{$formation->titre}\" depuis plus de 24h. Continuez votre apprentissage !",
                'rappel'
            );

            $this->info("Rappel envoyé à {$user->prenom} pour : {$formation->titre}");
        }

        $this->info('Commande rappel terminée.');
    }
}