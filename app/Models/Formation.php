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
    ];

    protected $casts = [
        'prerequis'    => 'array',
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
}
