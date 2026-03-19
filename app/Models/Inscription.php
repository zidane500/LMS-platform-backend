<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inscription extends Model
{
    // On désactive les timestamps automatiques (created_at/updated_at)
    // car on a notre propre champ date_inscription
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'formation_id',
        'date_inscription',
    ];

    protected $casts = [
        'date_inscription' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function formation()
    {
        return $this->belongsTo(Formation::class);
    }
}
