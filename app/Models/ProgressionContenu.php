<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgressionContenu extends Model
{
    protected $table = 'progression_contenus';

    protected $fillable = [
        'user_id', 'contenu_id', 'complete', 'pourcentage', 'derniere_consultation',
    ];

    protected $casts = [
        'complete'              => 'boolean',
        'pourcentage'           => 'integer',
        'derniere_consultation' => 'datetime',
    ];

    public function user()    { return $this->belongsTo(User::class); }
    public function contenu() { return $this->belongsTo(Contenu::class, 'contenu_id'); }
}
