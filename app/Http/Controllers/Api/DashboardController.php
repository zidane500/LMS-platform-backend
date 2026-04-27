<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Models\Inscription;
use App\Models\ProgressionFormation;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\TempsApprentissage;
use App\Models\Certificat;
use App\Models\ReponseApprenant;
use App\Models\TentativeQuiz;
use Illuminate\Support\Facades\DB;

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
    // ── Formateur/Admin : liste de ses formations ───────────
public function mesFormations(Request $request)
{
    $user = $request->user();

    $query = Formation::query();

    if ($user->role !== 'admin') {
        // Formateur → ses formations seulement
        $query->where('formateur_id', $user->id);
    } elseif ($request->query('mine') === 'true') {
        // ✅ Admin avec ?mine=true → ses formations créées personnellement
        $query->where('formateur_id', $user->id);
    }
    // Admin sans mine=true → toutes les formations

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

    public function usersStats(Request $request)
{
    $user = $request->user();
    if ($user->role !== 'admin') {
        return response()->json(['message' => 'Non autorisé'], 403);
    }

    $nbApprenants  = \App\Models\User::where('role', 'apprenant')->count();
    $nbFormateurs  = \App\Models\User::where('role', 'formateur')->count();
    $nbAdmins      = \App\Models\User::where('role', 'admin')->count();

    return response()->json([
        'labels' => ['Apprenants', 'Formateurs', 'Admins'],
        'data'   => [$nbApprenants, $nbFormateurs, $nbAdmins],
        'total'  => $nbApprenants + $nbFormateurs + $nbAdmins,
    ]);
}

// ── Admin : top N formations par inscriptions ───────────
// GET /api/dashboard/top-formations?limit=10
public function topFormations(Request $request)
{
    $user = $request->user();
    if ($user->role !== 'admin') {
        return response()->json(['message' => 'Non autorisé'], 403);
    }

    $limit = min(50, max(1, (int) $request->query('limit', 10)));

    $formations = Formation::withCount('inscriptions')
        ->where('statut', 'publie')
        ->orderByDesc('inscriptions_count')
        ->limit($limit)
        ->get(['id', 'titre'])
        ->map(fn($f) => [
            'titre'       => $f->titre,
            'inscriptions' => $f->inscriptions_count,
        ]);

    return response()->json([
        'formations' => $formations,
        'limit'      => $limit,
    ]);
}



// ── 1. Temps apprentissage par formation (apprenant/formateur) ──
// GET /api/dashboard/temps-apprentissage
public function tempsApprentissage(Request $request)
{
    $user = $request->user();

    $temps = TempsApprentissage::where('user_id', $user->id)
        ->with('formation:id,titre')
        ->orderByDesc('duree_secondes')
        ->get()
        ->map(fn($t) => [
            'formation_titre'  => $t->formation?->titre ?? 'Formation inconnue',
            'duree_secondes'   => $t->duree_secondes,
            'duree_minutes'    => round($t->duree_secondes / 60, 1),
        ]);

    return response()->json($temps);
}

// ── 2. Enregistrer du temps passé ──────────────────────────────
// POST /api/formations/{id}/temps
public function enregistrerTemps(Request $request, $formationId)
    {
        $request->validate(['duree_secondes' => 'required|integer|min:1|max:86400']);
        $user = $request->user();

        TempsApprentissage::updateOrCreate(
            ['user_id' => $user->id, 'formation_id' => $formationId],
            // ✅ FIX : DB:: au lieu de \DB::
            ['duree_secondes' => DB::raw("duree_secondes + {$request->duree_secondes}")]
        );

        return response()->json(['message' => 'Temps enregistré']);
    }


// ── 3. Admin : formations nécessitant attention ──────────────────
// GET /api/dashboard/formations-attention
public function formationsAttention(Request $request)
{
    if ($request->user()->role !== 'admin') {
        return response()->json(['message' => 'Non autorisé'], 403);
    }

    $formations = Formation::with(['inscriptions', 'modules.quiz'])
        ->where('statut', 'publie')
        ->get()
        ->map(function ($f) {
            $nbInscrits = $f->inscriptions->count();
            if ($nbInscrits === 0) return null;

            // Progression moyenne
            $progressions = ProgressionFormation::where('formation_id', $f->id)->get();
            $progMoyenne  = $progressions->count() > 0
                ? round($progressions->avg('pourcentage_global'), 1)
                : 0;

            // Taux d'échec quiz
            $quizIds = $f->modules->map(fn($m) => optional($m->quiz)->id)->filter()->values();
            $nbTentatives = TentativeQuiz::whereIn('quiz_id', $quizIds)->count();
            $nbEchecs     = TentativeQuiz::whereIn('quiz_id', $quizIds)->where('reussi', false)->count();
            $tauxEchec    = $nbTentatives > 0 ? round(($nbEchecs / $nbTentatives) * 100, 1) : 0;

            // Taux d'abandon (inscrits mais < 10% progression)
            $nbAbandons = $progressions->where('pourcentage_global', '<', 10)->count();
            $tauxAbandon = $nbInscrits > 0 ? round(($nbAbandons / $nbInscrits) * 100, 1) : 0;

            // Score d'attention (plus il est élevé, plus la formation a besoin d'attention)
            $score = 0;
            $alertes = [];

            if ($progMoyenne < 40) { $score += 3; $alertes[] = "Progression faible ({$progMoyenne}%)"; }
            elseif ($progMoyenne < 60) { $score += 1; $alertes[] = "Progression modérée ({$progMoyenne}%)"; }

            if ($tauxEchec > 60) { $score += 3; $alertes[] = "Taux d'échec élevé ({$tauxEchec}%)"; }
            elseif ($tauxEchec > 40) { $score += 1; $alertes[] = "Taux d'échec modéré ({$tauxEchec}%)"; }

            if ($tauxAbandon > 50) { $score += 2; $alertes[] = "Beaucoup d'abandons ({$tauxAbandon}%)"; }

            if ($score === 0) return null;

            return [
                'titre'        => $f->titre,
                'nb_inscrits'  => $nbInscrits,
                'prog_moyenne' => $progMoyenne,
                'taux_echec'   => $tauxEchec,
                'taux_abandon' => $tauxAbandon,
                'score'        => $score,
                'alertes'      => $alertes,
                'niveau'       => $score >= 5 ? 'critique' : ($score >= 3 ? 'attention' : 'faible'),
            ];
        })
        ->filter()
        ->sortByDesc('score')
        ->values();

    return response()->json($formations);
}

// ── 4. Admin : stats réponses IA (Gemini) ─────────────────────
// GET /api/dashboard/ia-stats
public function iaStats(Request $request)
{
    if ($request->user()->role !== 'admin') {
        return response()->json(['message' => 'Non autorisé'], 403);
    }

    // Stats globales IA
    $reponsesIA = ReponseApprenant::whereNotNull('score_ia')->get();
    $total      = $reponsesIA->count();

    if ($total === 0) {
        return response()->json([
            'total_corrigees'   => 0,
            'score_moyen'       => 0,
            'insuffisantes'     => 0,
            'par_formation'     => [],
        ]);
    }

    $scoreMoyen    = round($reponsesIA->avg('score_ia'), 1);
    $insuffisantes = $reponsesIA->where('est_correct', false)->count();

    // Stats par formation (via tentatives → quiz → module → formation)
    $parFormation = Formation::where('statut', 'publie')
        ->with(['modules.quiz'])
        ->get()
        ->map(function ($f) {
            $quizIds = $f->modules->map(fn($m) => optional($m->quiz)->id)->filter()->values();
            if ($quizIds->isEmpty()) return null;

            $tentativeIds = TentativeQuiz::whereIn('quiz_id', $quizIds)->pluck('id');
            $reponsesIA   = ReponseApprenant::whereIn('tentative_id', $tentativeIds)
                ->whereNotNull('score_ia')->get();

            if ($reponsesIA->isEmpty()) return null;

            return [
                'titre'           => $f->titre,
                'nb_corrigees'    => $reponsesIA->count(),
                'score_moyen_ia'  => round($reponsesIA->avg('score_ia'), 1),
                'nb_insuffisantes'=> $reponsesIA->where('est_correct', false)->count(),
            ];
        })
        ->filter()
        ->sortByDesc('nb_corrigees')
        ->values()
        ->take(8);

    return response()->json([
        'total_corrigees'  => $total,
        'score_moyen'      => $scoreMoyen,
        'insuffisantes'    => $insuffisantes,
        'par_formation'    => $parFormation,
    ]);
}

// ── 5. Admin : statut des certifications ───────────────────────
// GET /api/dashboard/certifications-stats
public function certificationsStats(Request $request)
{
    if ($request->user()->role !== 'admin') {
        return response()->json(['message' => 'Non autorisé'], 403);
    }

    $certifies    = Certificat::distinct('user_id')->count('user_id');
    $enCours      = ProgressionFormation::where('complete', false)
                        ->where('pourcentage_global', '>', 0)
                        ->distinct('user_id')->count('user_id');
    $nonEligibles = \App\Models\User::whereIn('role', ['apprenant', 'formateur'])->count()
                    - $certifies - $enCours;

    return response()->json([
        'labels' => ['Certifiés', 'En cours', 'Non éligibles'],
        'data'   => [
            max(0, $certifies),
            max(0, $enCours),
            max(0, $nonEligibles),
        ],
        'total'  => $certifies + $enCours + max(0, $nonEligibles),
    ]);
}

// ── 6. Admin : progression moyenne par catégorie ───────────────
// GET /api/dashboard/progression-par-categorie
public function progressionParCategorie(Request $request)
{
    if ($request->user()->role !== 'admin') {
        return response()->json(['message' => 'Non autorisé'], 403);
    }

    $categories = Formation::where('statut', 'publie')
        ->select('categorie')
        ->distinct()
        ->pluck('categorie');

    $result = $categories->map(function ($cat) {
        $formationIds = Formation::where('statut', 'publie')
            ->where('categorie', $cat)
            ->pluck('id');

        $progressions = ProgressionFormation::whereIn('formation_id', $formationIds)->get();
        $moyenne = $progressions->count() > 0
            ? round($progressions->avg('pourcentage_global'), 1)
            : 0;

        return [
            'categorie' => $cat,
            'moyenne'   => $moyenne,
            'nb_apprenants' => $progressions->count(),
        ];
    })->sortByDesc('moyenne')->values();

    return response()->json($result);
}


}