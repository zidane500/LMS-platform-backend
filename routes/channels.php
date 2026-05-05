<?php

use App\Models\Formation;
use App\Models\Inscription;
use Illuminate\Support\Facades\Broadcast;

// ✅ NE PAS rappeler Broadcast::routes() ici — déjà fait dans BroadcastServiceProvider
// Broadcast::routes(['middleware' => ['auth:sanctum']]); ← SUPPRIMER CETTE LIGNE

Broadcast::channel('conversation.{formationId}.{id1}.{id2}', function ($user, $formationId, $id1, $id2) {
    $participants = [(int) $id1, (int) $id2];

    if (!in_array($user->id, $participants)) {
        return false;
    }

    $formation = Formation::find($formationId);
    if (!$formation) return false;

    if ($user->role === 'admin') return true;
    if ($formation->formateur_id === $user->id) return true;

    return Inscription::where('user_id', $user->id)
        ->where('formation_id', $formationId)
        ->exists();
});