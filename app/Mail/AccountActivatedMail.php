<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountActivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $loginUrl;
    public string $roleLabel;

    /**
     * @param User $user        L'utilisateur validé
     * @param string $role      Son rôle (etudiant, formateur, admin)
     */
    public function __construct(User $user, string $role = 'etudiant')
    {
        $this->user = $user;
        $this->roleLabel = match ($role) {
            'etudiant'  => 'Étudiant',
            'formateur' => 'Formateur',
            'admin'     => 'Administrateur',
            default     => 'Utilisateur',
        };

        // URL dynamique selon le rôle
        $frontendUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');

        $this->loginUrl = match ($role) {
            'etudiant'  => "{$frontendUrl}/login?redirect=/etudiant",
            'formateur' => "{$frontendUrl}/login?redirect=/formateur",
            'admin'     => "{$frontendUrl}/login?redirect=/admin",
            default     => "{$frontendUrl}/login",
        };
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre compte Ny Tiona Digital est activé !',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-activated',
            with: [
                'user'       => $this->user,
                'loginUrl'   => $this->loginUrl,
                'roleLabel'  => $this->roleLabel,
                'fullName'   => $this->user->profile?->nom_complet
                                ?? $this->user->nom_complet
                                ?? $this->user->email,
                'frontendUrl' => config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')),
            ],
        );
    }
}