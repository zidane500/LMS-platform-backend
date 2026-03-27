<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contenu extends Model
{
    protected $table = 'contenus';

    protected $fillable = [
        'module_id', 'titre', 'type', 'url',
        'chemin_fichier', 'duree', 'resume', 'miniature', 'ordre',
    ];

    protected $casts = [
        'duree' => 'integer',
        'ordre' => 'integer',
    ];

    public function module()
    {
        return $this->belongsTo(ModuleFormation::class, 'module_id');
    }

    public function progressions()
    {
        return $this->hasMany(ProgressionContenu::class, 'contenu_id');
    }
}
