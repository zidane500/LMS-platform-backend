<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Notifications\ResetPassword;
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
    ];

    protected $hidden = [
        'mot_de_passe',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_naissance'    => 'date',
    ];

    public function getAuthPassword(): string
    {
        return $this->mot_de_passe;
    }

    public function getAuthPasswordName(): string
    {
        return 'mot_de_passe';
    }

    public function apprenant()
    {
        return $this->hasOne(Apprenant::class);
    }

    /**
     * Cette méthode dit à Laravel :
     * "Pour le lien de réinitialisation, utilise l'URL du frontend React
     *  au lieu de chercher une route Laravel qui n'existe pas."
     */
    public function sendPasswordResetNotification($token): void
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
        $url = $frontendUrl . '/reset-password?token=' . $token . '&email=' . urlencode($this->email);

        ResetPassword::createUrlUsing(fn() => $url);

        $this->notify(new ResetPassword($token));
    }
}