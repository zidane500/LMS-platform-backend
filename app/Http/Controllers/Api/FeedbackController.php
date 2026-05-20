<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Formation;
use App\Models\Inscription;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    // GET /api/formations/{id}/feedbacks — public
    public function index(Request $request, $formationId)
    {
        $feedbacks = Feedback::where('formation_id', $formationId)
            ->with('user:id,prenom,nom,photo_profil')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($f) => [
                'id'          => $f->id,
                'note'        => $f->note,
                'commentaire' => $f->commentaire,
                'created_at'  => $f->created_at?->locale('fr')->diffForHumans(),
                'user'        => [
                    'nom'    => $f->user->prenom . ' ' . $f->user->nom,
                    'avatar' => $f->user->photo_profil
                        ? asset('storage/' . $f->user->photo_profil)
                        : null,
                    'initiale' => mb_strtoupper(mb_substr($f->user->prenom, 0, 1)),
                ],
                'reponse_formateur' => $f->reponse_formateur,
                'repondu_le'        => $f->repondu_le?->locale('fr')->diffForHumans(),
            ]);

        $moyenne = $feedbacks->avg('note');

        return response()->json([
            'feedbacks' => $feedbacks,
            'moyenne'   => $moyenne ? round($moyenne, 1) : null,
            'total'     => $feedbacks->count(),
        ]);
    }

    // POST /api/formations/{id}/feedbacks — authentifié
    public function store(Request $request, $formationId)
    {
        $user = $request->user();

        // Vérifier inscrit ou formateur propriétaire
        $formation = Formation::findOrFail($formationId);
        $aAcces = $user->role === 'admin'
            || $formation->formateur_id === $user->id
            || Inscription::where('user_id', $user->id)
                ->where('formation_id', $formationId)->exists();

        if (!$aAcces) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $request->validate([
            'note'        => 'required|integer|min:1|max:6',
            'commentaire' => 'nullable|string|max:1000',
        ]);

        $feedback = Feedback::updateOrCreate(
            ['formation_id' => $formationId, 'user_id' => $user->id],
            ['note' => $request->note, 'commentaire' => $request->commentaire]
        );

        return response()->json([
            'message'  => 'Feedback enregistré',
            'feedback' => [
                'id'          => $feedback->id,
                'note'        => $feedback->note,
                'commentaire' => $feedback->commentaire,
            ],
        ], 201);
    }

    // GET /api/formations/{id}/feedbacks/mon-feedback
    public function monFeedback(Request $request, $formationId)
    {
        $user     = $request->user();
        $feedback = Feedback::where('formation_id', $formationId)
            ->where('user_id', $user->id)
            ->first();

        return response()->json($feedback ? [
            'note'        => $feedback->note,
            'commentaire' => $feedback->commentaire,
        ] : null);
    }

    public function update(Request $request, $id)
{
    // ✅ L'admin ne peut plus modifier les feedbacks des utilisateurs
    return response()->json([
        'message' => 'La modification des feedbacks n\'est pas autorisée.',
    ], 403);
}

/**
 * Supprimer un feedback (admin seulement)
 */
public function destroy(Request $request, $id)
{
    $user = $request->user();
    
    // ✅ Seul admin peut supprimer
    if ($user->role !== 'admin') {
        return response()->json(['message' => 'Non autorisé'], 403);
    }
    
    $feedback = Feedback::findOrFail($id);
    $feedback->delete();
    
    return response()->json([
        'message' => 'Feedback supprimé avec succès'
    ]);
}
public function repondre(Request $request, $id)
{
    $user     = $request->user();
    $feedback = \App\Models\Feedback::with('formation')->findOrFail($id);

    // ✅ Seul le propriétaire de la formation peut répondre
    // L'admin n'a plus le droit de répondre/modifier les réponses
    $isOwner = $feedback->formation->formateur_id === $user->id;

    if (!$isOwner) {
        return response()->json([
            'message' => 'Seul le formateur de cette formation peut répondre aux feedbacks.',
        ], 403);
    }

    // ✅ Si une réponse existe déjà, elle ne peut plus être modifiée
    if (!empty($feedback->reponse_formateur)) {
        return response()->json([
            'message' => 'Vous avez déjà répondu à ce feedback. Une réponse publiée ne peut plus être modifiée.',
        ], 403);
    }

    $request->validate([
        'reponse' => 'required|string|min:5|max:1000',
    ]);

    $feedback->update([
        'reponse_formateur' => $request->reponse,
        'repondu_le'        => now(),
    ]);

    return response()->json([
        'message'           => 'Réponse publiée avec succès.',
        'reponse_formateur' => $feedback->reponse_formateur,
        'repondu_le'        => $feedback->repondu_le?->locale('fr')->diffForHumans(),
    ]);
}
}