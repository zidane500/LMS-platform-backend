<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Models\Inscription;
use App\Models\ProgressionFormation;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // ── Formateur/Admin : inscriptions par semaine ──────────
    // GET /api/dashboard/inscriptions-semaine?formation_id=X
    public function inscriptionsParSemaine(Request $request)
    {
        $user = $request->user();

        // Récupérer la formation cible
        $formationId = $request->query('formation_id');

        // Vérifier que la formation appartient à cet utilisateur (sauf admin)
        $query = Formation::query();
        if ($user->role !== 'admin') {
            $query->where('formateur_id', $user->id);
        }

        $formation = $query->findOrFail($formationId);

        // Générer les 8 dernières semaines
        $semaines = [];
        $labels   = [];

        for ($i = 7; $i >= 0; $i--) {
            $debut  = Carbon::now()->startOfWeek()->subWeeks($i);
            $fin    = $debut->copy()->endOfWeek();

            $count = Inscription::where('formation_id', $formation->id)
                ->whereBetween('date_inscription', [$debut, $fin])
                ->count();

            $semaines[] = $count;
            $labels[]   = 'S' . $debut->weekOfYear;
        }

        return response()->json([
            'labels'       => $labels,
            'data'         => $semaines,
            'formation'    => $formation->titre,
        ]);
    }

    // ── Formateur/Admin : liste de ses formations ───────────
    // GET /api/dashboard/mes-formations
    public function mesFormations(Request $request)
    {
        $user = $request->user();

        $query = Formation::query();
        if ($user->role !== 'admin') {
            $query->where('formateur_id', $user->id);
        }

        $formations = $query->select('id', 'titre')->get();

        return response()->json($formations);
    }

    // ── Apprenant : stats pour ses graphiques ──────────────
    // GET /api/dashboard/apprenant/stats
    public function apprenantStats(Request $request)
    {
        $user = $request->user();

        // Total formations publiées sur la plateforme
        $totalFormations = Formation::where('statut', 'publie')->count();

        // Formations complétées par l'apprenant
        $completees = ProgressionFormation::where('user_id', $user->id)
            ->where('complete', true)
            ->count();

        // Formations complétées par mois (12 derniers mois)
        $labels = [];
        $data   = [];

        for ($i = 11; $i >= 0; $i--) {
            $mois  = Carbon::now()->subMonths($i);
            $debut = $mois->copy()->startOfMonth();
            $fin   = $mois->copy()->endOfMonth();

            $count = ProgressionFormation::where('user_id', $user->id)
                ->where('complete', true)
                ->whereBetween('updated_at', [$debut, $fin])
                ->count();

            $labels[] = $mois->locale('fr')->isoFormat('MMM YY');
            $data[]   = $count;
        }

        return response()->json([
            'total_formations'       => $totalFormations,
            'formations_completees'  => $completees,
            'formations_en_cours'    => $totalFormations - $completees,
            'labels_mois'            => $labels,
            'data_mois'              => $data,
        ]);
    }
}