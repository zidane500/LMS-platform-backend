<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgressionFormation extends Model
{
    protected $table = 'progression_formations';

    protected $fillable = [
        'user_id',
        'formation_id',
        'pourcentage_global',
        'modules_completes',
        'contenus_completes',
        'complete',
        'termine_le',
    ];

    protected $casts = [
        'pourcentage_global' => 'integer',
        'modules_completes' => 'integer',
        'contenus_completes' => 'integer',
        'complete' => 'boolean',
        'termine_le' => 'datetime',
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