<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TempsApprentissage extends Model
{
    protected $table    = 'temps_apprentissage';
    protected $fillable = ['user_id', 'formation_id', 'duree_secondes'];

    public function user()      { return $this->belongsTo(User::class); }
    public function formation() { return $this->belongsTo(Formation::class); }
}