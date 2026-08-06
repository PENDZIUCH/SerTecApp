<?php

namespace App\Mail;

use App\Models\WorkPart;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ParteCompletadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public WorkPart $parte) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Parte de Servicio #{$this->parte->work_order_id} — Trabajo Completado",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.parte-completado',
            with: [
                'parte' => $this->parte,
                'order' => $this->parte->workOrder,
                'customer' => $this->parte->workOrder->customer,
                'technician' => $this->parte->technician,
            ]
        );
    }
}
