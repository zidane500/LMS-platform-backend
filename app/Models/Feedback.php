<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    
    protected $table = 'feedbacks';
    
    protected $fillable = 
    [
     'formation_id',
     'user_id',
     'note',
     'reponse_formateur',
     'repondu_le',
     'commentaire'
     ];

     protected $casts = [
    'repondu_le' => 'datetime',
    ];

    public function user()      { return $this->belongsTo(User::class); }
    public function formation() { return $this->belongsTo(Formation::class); }
}