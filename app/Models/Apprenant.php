<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Apprenant extends Model
{
    protected $fillable = [
        'user_id',
        'domaines_cibles',
        'technologies',
    ];

    protected $casts = [
        'domaines_cibles' => 'array',
        'technologies' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}