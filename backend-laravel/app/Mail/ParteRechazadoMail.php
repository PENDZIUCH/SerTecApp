<?php

namespace App\Mail;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ParteRechazadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public WorkOrder $order, public ?string $nuevoTecnico = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Actualización de su Orden #' . str_pad($this->order->id, 4, '0', STR_PAD_LEFT),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.parte-rechazado',
            with: [
                'order'       => $this->order,
                'customer'    => $this->order->customer,
                'nuevoTecnico' => $this->nuevoTecnico,
            ]
        );
    }
}
