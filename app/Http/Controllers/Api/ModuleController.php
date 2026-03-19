<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Models\ModuleFormation;
use App\Models\User;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    // ─── US 2.2 : Ajouter un module à une formation ───────
    public function store(Request $request, $formationId)
    {
        $user      = $request->user();
        $formation = Formation::findOrFail($formationId);

        $this->authorize_owner_or_admin($user, $formation);

        $validated = $request->validate([
            'titre'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'duree'       => 'required|integer|min:0',
        ]);

        // Ordre = dernier module + 1 (ajout à la fin)
        $maxOrdre = ModuleFormation::where('formation_id', $formationId)->max('ordre') ?? 0;

        $module = ModuleFormation::create([
            ...$validated,
            'formation_id' => $formationId,
            'ordre'        => $maxOrdre + 1,
        ]);

        return response()->json([
            'message' => 'Module ajouté avec succès',
            'module'  => $this->formatModule($module),
        ], 201);
    }

    // ─── US 2.2 : Modifier un module ──────────────────────
    public function update(Request $request, $formationId, $moduleId)
    {
        $user      = $request->user();
        $formation = Formation::findOrFail($formationId);
        $module    = ModuleFormation::where('formation_id', $formationId)->findOrFail($moduleId);

        $this->authorize_owner_or_admin($user, $formation);

        $validated = $request->validate([
            'titre'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'duree'       => 'sometimes|required|integer|min:0',
        ]);

        $module->update($validated);

        return response()->json([
            'message' => 'Module mis à jour',
            'module'  => $this->formatModule($module),
        ]);
    }

    // ─── US 2.2 : Supprimer un module ─────────────────────
    public function destroy(Request $request, $formationId, $moduleId)
    {
        $user      = $request->user();
        $formation = Formation::findOrFail($formationId);
        $module    = ModuleFormation::where('formation_id', $formationId)->findOrFail($moduleId);

        $this->authorize_owner_or_admin($user, $formation);

        $ordreSuppr = $module->ordre;
        $module->delete();

        // Réajuster les ordres des modules suivants
        ModuleFormation::where('formation_id', $formationId)
                       ->where('ordre', '>', $ordreSuppr)
                       ->decrement('ordre');

        return response()->json(['message' => 'Module supprimé avec succès']);
    }

    // ─── US 2.5 : Réorganiser l'ordre des modules (drag & drop) ──
    public function reorder(Request $request, $formationId)
    {
        $user      = $request->user();
        $formation = Formation::findOrFail($formationId);

        $this->authorize_owner_or_admin($user, $formation);

        $request->validate([
            'ordre' => 'required|array',        // Tableau d'IDs dans le nouvel ordre
            'ordre.*' => 'required|integer',
        ]);

        // Met à jour l'ordre de chaque module
        foreach ($request->ordre as $position => $moduleId) {
            ModuleFormation::where('id', $moduleId)
                           ->where('formation_id', $formationId)
                           ->update(['ordre' => $position + 1]);
        }

        return response()->json(['message' => 'Ordre des modules sauvegardé']);
    }

    // ─── Méthodes privées ─────────────────────────────────
    private function authorize_owner_or_admin(User $user, Formation $formation): void
    {
        if ($user->role === 'admin') return;
        if ($user->role === 'formateur' && $formation->formateur_id === $user->id) return;
        abort(403, 'Vous n\'êtes pas autorisé.');
    }

    private function formatModule(ModuleFormation $m): array
    {
        return [
            'id'           => (string) $m->id,
            'formation_id' => (string) $m->formation_id,
            'titre'        => $m->titre,
            'description'  => $m->description,
            'duree'        => $m->duree,
            'ordre'        => $m->ordre,
        ];
    }
}
