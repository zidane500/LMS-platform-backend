<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $table = 'questions';

    protected $fillable = [
        'quiz_id',
         'texte',
          'type',
           'points',
            'ordre',
            'correction_attendue',
    ];

    protected $casts = [
        'points' => 'integer',
        'ordre'  => 'integer',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function choix()
    {
        return $this->hasMany(ChoixReponse::class)->orderBy('ordre');
    }
}
