<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'prenom',
        'nom',
        'email',
        'mot_de_passe',
        'telephone',
        'date_naissance',
        'photo_profil',
        'langue_preferee',
        'role',
        'google2fa_secret',
        'google2fa_enabled',
    ];

    protected $hidden = [
        'mot_de_passe',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_naissance'    => 'date',
        'peut_coder' => 'boolean',
    ];

    // Nécessaire pour Laravel Auth (champ non standard)
    public function getAuthPassword(): string
    {
        return $this->mot_de_passe;
    }

    // Nécessaire pour le Password Broker (réinitialisation)
    public function getAuthPasswordName(): string
    {
        return 'mot_de_passe';
    }


  public function sendPasswordResetNotification($token): void
{
    $this->notify(new \App\Notifications\ResetPasswordNotification($token));
}


    // Relations
    public function apprenant()
    {
        return $this->hasOne(Apprenant::class);
    }

    public function formateur()
    {
        return $this->hasOne(Formateur::class);
    }

    public function demandeFormateur()
    {
        return $this->hasOne(DemandeFormateur::class);
    }

    // Formations créées par cet utilisateur (en tant que formateur)
    public function formations()
    {
        return $this->hasMany(Formation::class, 'formateur_id');
    }

    // Inscriptions de cet utilisateur (en tant qu'apprenant)
    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    
}
