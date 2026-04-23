<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Certificat extends Model
{
    protected $table    = 'certificats';
    protected $fillable = ['numero', 'user_id', 'formation_id', 'moyenne', 'mention', 'emis_le'];
    protected $casts    = ['emis_le' => 'datetime', 'moyenne' => 'float'];

    public function user()      { return $this->belongsTo(User::class); }
    public function formation() { return $this->belongsTo(Formation::class); }
}