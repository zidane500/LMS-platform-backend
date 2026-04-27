<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Apprenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // ─── US 1.6 : Modifier son propre profil ──────────────
    public function updateProfile(Request $request)
    {
        $user = $request->user(); // L'utilisateur connecté

        $validated = $request->validate([
            'prenom'          => 'required|string|max:100',
            'nom'             => 'required|string|max:100',
            'telephone'       => 'nullable|string|max:20',
            'date_naissance'  => 'nullable|date',
            'langue_preferee' => 'nullable|string|max:10',
            'domaines_cibles' => 'nullable|array',
            'technologies'    => 'nullable|array',
            'photo_profil'    => 'nullable|image|max:5120', // max 5MB
        ]);

        // Mise à jour des champs texte
        $user->prenom          = $validated['prenom'];
        $user->nom             = $validated['nom'];
        $user->telephone       = $validated['telephone'] ?? $user->telephone;
        $user->date_naissance  = $validated['date_naissance'] ?? $user->date_naissance;
        $user->langue_preferee = $validated['langue_preferee'] ?? $user->langue_preferee;

        // Upload de la photo de profil
        if ($request->hasFile('photo_profil')) {
            // Supprimer l'ancienne photo si elle existe
            if ($user->photo_profil) {
                Storage::disk('public')->delete($user->photo_profil);
            }
            // Sauvegarder la nouvelle photo
            $path = $request->file('photo_profil')->store('photos_profil', 'public');
            $user->photo_profil = $path;
        }

        $user->save();

        // Mettre à jour les données de l'apprenant si applicable
        if ($user->role === 'apprenant' && $user->apprenant) {
            $user->apprenant->update([
                'domaines_cibles' => $validated['domaines_cibles'] ?? $user->apprenant->domaines_cibles,
                'technologies'    => $validated['technologies'] ?? $user->apprenant->technologies,
            ]);
        }

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'user'    => $this->formatUser($user->fresh()),
        ]);
    }

    // ─── US 1.7 (admin) : Lister tous les utilisateurs ────
    public function index(Request $request)
    {
        // Vérification du rôle admin
        $this->authorize_admin($request->user());

        $query = User::with('apprenant', 'formateur');

        // Filtre par rôle
        if ($request->has('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        // Recherche par nom/email
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('prenom', 'ilike', "%$search%")
                  ->orWhere('nom', 'ilike', "%$search%")
                  ->orWhere('email', 'ilike', "%$search%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        return response()->json(
            $users->map(fn($u) => $this->formatUser($u))
        );
    }

    // ─── US 1.7 (admin) : Modifier un utilisateur ─────────
   // ─── US 1.7 (admin) : Modifier un utilisateur ─────────
public function update(Request $request, $id)
{
    $this->authorize_admin($request->user());

    $user = User::findOrFail($id);

    $validated = $request->validate([
        'prenom'          => 'sometimes|required|string|max:100',
        'nom'             => 'sometimes|required|string|max:100',
        'email'           => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($id)],
        'role'            => 'sometimes|required|in:apprenant,formateur,admin',
        // ✅ Nouveaux champs
        'telephone'       => 'nullable|string|max:20',
        'date_naissance'  => 'nullable|date',
        'langue_preferee' => 'nullable|string|max:10',
        'domaines_cibles' => 'nullable|array',
        'technologies'    => 'nullable|array',
        'photo_profil'    => 'nullable|image|max:5120',
    ]);

    // ✅ Upload photo si fournie
    if ($request->hasFile('photo_profil')) {
        if ($user->photo_profil) {
            Storage::disk('public')->delete($user->photo_profil);
        }
        $path = $request->file('photo_profil')->store('photos_profil', 'public');
        $user->photo_profil = $path;
    }

    // Champs texte
    if (isset($validated['prenom']))          $user->prenom          = $validated['prenom'];
    if (isset($validated['nom']))             $user->nom             = $validated['nom'];
    if (isset($validated['email']))           $user->email           = $validated['email'];
    if (isset($validated['role']))            $user->role            = $validated['role'];
    if (array_key_exists('telephone', $validated))       $user->telephone       = $validated['telephone'];
    if (array_key_exists('date_naissance', $validated))  $user->date_naissance  = $validated['date_naissance'];
    if (array_key_exists('langue_preferee', $validated)) $user->langue_preferee = $validated['langue_preferee'];

    $user->save();

    // ✅ Mettre à jour apprenant si applicable
    if ($user->role === 'apprenant' && $user->apprenant) {
        if (array_key_exists('domaines_cibles', $validated))
            $user->apprenant->domaines_cibles = $validated['domaines_cibles'];
        if (array_key_exists('technologies', $validated))
            $user->apprenant->technologies = $validated['technologies'];
        $user->apprenant->save();
    }

    return response()->json([
        'message' => 'Utilisateur modifié avec succès',
        'user'    => $this->formatUser($user->fresh()),
    ]);
}

    // ─── US 1.7 (admin) : Supprimer un utilisateur ────────
    public function destroy(Request $request, $id)
    {
        $this->authorize_admin($request->user());

        // On ne peut pas se supprimer soi-même
        if ($request->user()->id == $id) {
            return response()->json([
                'message' => 'Impossible de supprimer votre propre compte.',
            ], 403);
        }

        $user = User::findOrFail($id);
        $user->delete(); // Cascade grâce aux FK onDelete('cascade')

        return response()->json(['message' => 'Utilisateur supprimé avec succès']);
    }

    // ─── Méthode privée : vérifier que l'utilisateur est admin
    private function authorize_admin(User $user): void
    {
        if ($user->role !== 'admin') {
            abort(403, 'Accès réservé aux administrateurs.');
        }
    }

    // ─── Format de réponse utilisateur ────────────────────
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
            'photo_profil'    => $user->photo_profil
                                    ? asset('storage/' . $user->photo_profil)
                                    : null,
            'langue_preferee' => $user->langue_preferee,
            'domaines_cibles' => $apprenant?->domaines_cibles ?? [],
            'technologies'    => $apprenant?->technologies ?? [],
             'peut_coder'      => (bool) ($user->peut_coder ?? false),
            'created_at'      => $user->created_at?->toISOString(),
        ];
    }

    // ─── Admin : activer/désactiver le droit de créer des formations codées ──
public function togglePeutCoder(Request $request, $id)
{
    if ($request->user()->role !== 'admin') {
        return response()->json(['message' => 'Accès refusé.'], 403);
    }

    $user = User::findOrFail($id);

    if ($user->role !== 'formateur') {
        return response()->json([
            'message' => 'Seuls les formateurs peuvent avoir ce droit.',
        ], 422);
    }

    $user->peut_coder = !$user->peut_coder;
    $user->save();

    return response()->json([
        'message'    => $user->peut_coder
            ? 'Formateur autorisé à créer des formations codées.'
            : 'Droit révoqué.',
        'peut_coder' => $user->peut_coder,
    ]);
}
}
