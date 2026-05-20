<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{
    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = config('app.frontend_url')
            . '/reset-password'
            . '?token=' . $this->token
            . '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe — LMS Platform')
            ->view('emails.reset-password', [
                'resetUrl' => $resetUrl,
                'userName' => trim(($notifiable->prenom ?? '') . ' ' . ($notifiable->nom ?? '')),
                'expireMin' => config('auth.passwords.users.expire', 60),
            ]);
    }
}