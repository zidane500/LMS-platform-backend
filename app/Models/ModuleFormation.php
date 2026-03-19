<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// On appelle ce modèle "Module" mais en PHP "Module" est un mot réservé
// donc on utilise ModuleFormation comme nom de classe
// et on spécifie la vraie table = 'modules'
class ModuleFormation extends Model
{
    protected $table = 'modules';

    protected $fillable = [
        'formation_id',
        'titre',
        'description',
        'duree',
        'ordre',
    ];

    protected $casts = [
        'duree'  => 'integer',
        'ordre'  => 'integer',
    ];

    // Relation vers la formation parente
    public function formation()
    {
        return $this->belongsTo(Formation::class);
    }
}
