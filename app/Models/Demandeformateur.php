<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandeFormateur extends Model
{
    // Pas de timestamps car la table a date_demande et date_traitement
    public $timestamps = false;

    protected $table = 'demandes_formateur';

    protected $fillable = [
        'user_id',
        'admin_id',
        'specialite',
        'experience_annees',
        'motivation',
        'langues_enseignees',
        'chemin_cv',
        'chemin_attestation',
        'statut',
        'date_demande',
        'date_traitement',
    ];

    protected $casts = [
        'langues_enseignees' => 'array',
        'date_demande'       => 'datetime',
        'date_traitement'    => 'datetime',
    ];

    // Relation vers le candidat
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relation vers l'admin qui a traité la demande
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
