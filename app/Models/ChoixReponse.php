<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ChoixReponse extends Model
{
    protected $table = 'choix_reponses';
    protected $fillable = ['question_id', 'texte', 'est_correct', 'ordre'];
    protected $casts    = ['est_correct' => 'boolean', 'ordre' => 'integer'];

    public function question() { return $this->belongsTo(Question::class); }
}
