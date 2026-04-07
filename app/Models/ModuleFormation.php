<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'duree' => 'integer',
        'ordre' => 'integer',
    ];

    public function formation()
    {
        return $this->belongsTo(Formation::class);
    }

    public function contenus()
    {
        return $this->hasMany(Contenu::class, 'module_id')->orderBy('ordre');
    }

    public function quiz()
    {
        return $this->hasOne(Quiz::class, 'module_id');
    }
}