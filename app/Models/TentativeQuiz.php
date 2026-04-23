<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TentativeQuiz extends Model
{
    protected $table    = 'tentatives_quiz';
    protected $fillable = [
        'quiz_id', 'user_id', 'score', 'score_max',
        'reussi', 'duree_secondes', 'termine_le',
    ];
    protected $casts = [
        'reussi'      => 'boolean',
        'score'       => 'float',
        'score_max'   => 'integer',
        'termine_le'  => 'datetime',
    ];

    public function quiz()    { return $this->belongsTo(Quiz::class); }
    public function user()    { return $this->belongsTo(User::class); }
    public function reponses(){ return $this->hasMany(ReponseApprenant::class, 'tentative_id'); }
}
