<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentValidatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public $paiement
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Paiement validé - Ny Tiona Digital',
        );
    }

    public function content(): Content
    {
        $moisLabel = $this->paiement->mois_label
            ?? (($this->paiement->mois ?? '') . '/' . ($this->paiement->annee ?? ''));

        return new Content(
            view: 'emails.payment-validated',
            with: [
                'userName' => $this->user->profile?->nom_complet
                    ?? $this->user->name
                    ?? 'Étudiant',
                'moisLabel' => $moisLabel,
                'montant' => number_format($this->paiement->montant ?? 0, 0, ',', ' '),
                'reference' => $this->paiement->reference ?? '',
            ],
        );
    }
}