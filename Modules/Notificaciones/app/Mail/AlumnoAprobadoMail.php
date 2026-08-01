<?php

declare(strict_types=1);

namespace Modules\Notificaciones\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class AlumnoAprobadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $alumno,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "¡Tu cuenta ha sido aprobada! — Prepárate y Postula Ya",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'notificaciones::emails.alumno-aprobado',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
