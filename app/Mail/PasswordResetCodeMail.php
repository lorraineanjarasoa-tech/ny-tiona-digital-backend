<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public string $code;

    public function __construct(
        User $user,
        string $code
    ) {
        $this->user = $user;
        $this->code = $code;
    }

    public function build()
    {
        return $this
            ->subject('Code de récupération de votre compte — Ny Tiona Digital')
            ->view('emails.password-reset-code');
    }
}