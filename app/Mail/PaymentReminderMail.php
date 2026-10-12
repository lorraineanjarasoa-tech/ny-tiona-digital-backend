<?php

namespace App\Mail;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public int $mois,
        public int $annee
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rappel : votre écolage arrive à échéance',
        );
    }

    public function content(): Content
    {
        $date = Carbon::createFromDate($this->annee, $this->mois, 1);

        return new Content(
            view: 'emails.payment-reminder',
            with: [
                'userName' => $this->user->profile?->nom_complet
                    ?? $this->user->name
                    ?? 'Étudiant',
                'moisLabel' => $date->translatedFormat('F Y'),
                'deadline' => "5 {$date->translatedFormat('F Y')}",
            ],
        );
    }
}