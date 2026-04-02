<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    protected $fillable = ['code', 'nom', 'description', 'icone', 'condition'];

    public function utilisateurs()
    {
        return $this->belongsToMany(User::class, 'badges_utilisateurs')
                    ->withPivot('formation_id', 'obtenu_le')
                    ->withTimestamps();
    }
}