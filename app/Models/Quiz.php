<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $table = 'quiz';

    protected $fillable = [
        'module_id', 'titre', 'description',
        'seuil_reussite', 'duree_minutes', 'nb_tentatives_max', 'statut',
    ];

    protected $casts = [
        'seuil_reussite'    => 'integer',
        'duree_minutes'     => 'integer',
        'nb_tentatives_max' => 'integer',
    ];

    public function module()
    {
        return $this->belongsTo(ModuleFormation::class, 'module_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('ordre');
    }

    public function tentatives()
    {
        return $this->hasMany(TentativeQuiz::class);
    }

    public function tentativesPour(int $userId)
    {
        return $this->tentatives()->where('user_id', $userId);
    }
}
