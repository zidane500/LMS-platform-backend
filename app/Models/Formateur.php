<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Formateur extends Model
{
    protected $fillable = [
        'user_id',
        'specialite',
        'experience_annees',
        'langues_enseignees',
    ];

    protected $casts = [
        'langues_enseignees' => 'array',
        'experience_annees'  => 'integer',
    ];

    // Un formateur appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
