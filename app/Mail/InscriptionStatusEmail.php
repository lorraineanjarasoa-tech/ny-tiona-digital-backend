// backend/app/Mail/InscriptionStatusEmail.php
<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InscriptionStatusEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $status
    ) {}

    public function envelope(): Envelope
    {
        $subject = match($this->status) {
            'en_attente' => 'Votre inscription est en attente de validation - SkillUp',
            'valide' => 'Votre inscription a été validée - SkillUp',
            'rejete' => 'Votre inscription a été rejetée - SkillUp',
            default => 'Mise à jour de votre inscription - SkillUp'
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inscription-status',
            with: [
                'user' => $this->user,
                'status' => $this->status
            ]
        );
    }
}
