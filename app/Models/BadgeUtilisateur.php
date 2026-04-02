<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BadgeUtilisateur extends Model
{
    protected $table = 'badges_utilisateurs';
    protected $fillable = ['user_id', 'badge_id', 'formation_id', 'obtenu_le'];
    protected $casts = ['obtenu_le' => 'datetime'];
}