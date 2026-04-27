<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormationAccesCode extends Model
{
    public $timestamps = false;

    protected $table    = 'formation_acces_code';
    protected $fillable = ['user_id', 'formation_id', 'accessed_at'];

    protected $casts = [
        'accessed_at' => 'datetime',
    ];

    public function user()      { return $this->belongsTo(User::class); }
    public function formation() { return $this->belongsTo(Formation::class); }
}