<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccesoUsuarioMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $accessUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu acceso a SerTecApp',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.acceso-usuario',
            with: [
                'user'      => $this->user,
                'accessUrl' => $this->accessUrl,
            ]
        );
    }
}
