<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Formation extends Model
{
    protected $fillable = [
    'formateur_id',
    'titre',
    'description',
    'categorie',
    'niveau',
    'duree_estimee',
    'prerequis',
    'miniature',
    'statut',
    'is_coded',
    'code',
];

    protected $casts = [
    'is_coded'      => 'boolean',
    'prerequis'     => 'array',
    'duree_estimee' => 'integer',
];

    // Relation vers le formateur (utilisateur)
    public function formateur()
    {
        return $this->belongsTo(User::class, 'formateur_id');
    }

    // Relation vers les modules (triés par ordre)
    public function modules()
    {
        return $this->hasMany(ModuleFormation::class)->orderBy('ordre');
    }

    // Relation vers les inscriptions
    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    // Formations prérequises de CETTE formation codée
public function prerequisFormations()
{
    return $this->belongsToMany(
        Formation::class,
        'formation_prerequis',
        'formation_codee_id',
        'prerequis_formation_id'
    );
}

// Formations codées qui REQUIÈRENT cette formation
public function formationsCodeesQuiRequerent()
{
    return $this->belongsToMany(
        Formation::class,
        'formation_prerequis',
        'prerequis_formation_id',
        'formation_codee_id'
    );
}

// Utilisateurs ayant débloqué cette formation codée
public function accesCode()
{
    return $this->hasMany(\App\Models\FormationAccesCode::class);
}

// Vérifier si un user a accès à cette formation codée
public function userAAcces(int $userId, string $userRole = 'apprenant'): bool
{
    // Formation non codée → accès libre
    if (!$this->is_coded) return true;

    // Admin → toujours accès
    if ($userRole === 'admin') return true;

    // Formateur propriétaire → accès à SA propre formation sans code
    if ($userRole === 'formateur' && $this->formateur_id === $userId) return true;

    // Sinon → vérifier si l'accès a été accordé via le code
    return $this->accesCode()->where('user_id', $userId)->exists();
}

}
