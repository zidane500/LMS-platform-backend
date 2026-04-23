<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ReponseApprenant extends Model
{
    protected $table    = 'reponses_apprenant';
    protected $fillable = [
     'tentative_id',
     'question_id',
      'choix_id',
       'reponse_texte',
        'est_correct',
         'score_ia',
          'feedback_ia',
           'points_forts',
            'points_amelioration',
             'points_obtenus',
             ];
   protected $casts = [
    'est_correct'   => 'boolean',
    'score_ia'      => 'float',     
    'points_obtenus'=> 'float',     
];

    public function tentative() { return $this->belongsTo(TentativeQuiz::class, 'tentative_id'); }
    public function question()  { return $this->belongsTo(Question::class); }
    public function choix()     { return $this->belongsTo(ChoixReponse::class, 'choix_id'); }
}
