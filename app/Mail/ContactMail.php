<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $email;
    public $subject;
    public $messageContent;

    public function __construct(
        string $name,
        string $email,
        string $subject,
        string $messageContent
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->subject = $subject;
        $this->messageContent = $messageContent;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouveau message de contact - ' . $this->subject,

            to: [
                new Address(
                    'nytionadigital@gmail.com',
                    'Ny Tiona Digital'
                )
            ],

            replyTo: [
                new Address(
                    $this->email,
                    $this->name
                )
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact',
            with: [
                'name' => $this->name,
                'email' => $this->email,
                'subject' => $this->subject,
                'messageContent' => $this->messageContent,
            ]
        );
    }
}