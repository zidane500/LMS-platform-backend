<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Notifications\ResetPassword;

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
    ];

    protected $hidden = [
        'mot_de_passe',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_naissance'    => 'date',
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
    // ✅ Enregistre l'URL frontend avant d'envoyer la notification
    \Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(
        function ($notifiable, $token) {
            return env('FRONTEND_URL', 'http://localhost:5173')
                . '/reset-password?token=' . $token
                . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
        }
    );

    $this->notify(new \Illuminate\Auth\Notifications\ResetPassword($token));
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
