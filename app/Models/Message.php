<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'formation_id', 'sender_id', 'receiver_id', 'contenu', 'lu_formateur',
        'type', 'media_url', 'media_nom', 'media_mime', 'reply_to_id', 'is_retracted',
    ];

    public function sender()    { return $this->belongsTo(User::class, 'sender_id'); }
    public function receiver()  { return $this->belongsTo(User::class, 'receiver_id'); }
    public function formation() { return $this->belongsTo(Formation::class); }
    public function replyTo()   {
        return $this->belongsTo(Message::class, 'reply_to_id')
                     ->with('sender:id,prenom,nom');
    }
    public function reactions() {
        return $this->hasMany(MessageReaction::class)
                     ->with('user:id,prenom,nom');
    }
}