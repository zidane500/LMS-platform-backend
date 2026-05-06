<?php

use App\Models\Formation;
use App\Models\Inscription;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('conversation.{formationId}.{id1}.{id2}', function ($user, $formationId, $id1, $id2) {
    $participants = [(int) $id1, (int) $id2];

    if (!in_array((int) $user->id, $participants, true)) {
        return false;
    }

    $formation = Formation::find($formationId);

    if (!$formation) {
        return false;
    }

    if ($user->role === 'admin') {
        return true;
    }

    if ((int) $formation->formateur_id === (int) $user->id) {
        return true;
    }

    return Inscription::where('user_id', $user->id)
        ->where('formation_id', $formationId)
        ->exists();
});

// Canal personnel pour les appels WebRTC
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});