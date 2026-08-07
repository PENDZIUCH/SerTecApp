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
            subject: 'Parte de Servicio #' . str_pad($this->parte->work_order_id, 4, '0', STR_PAD_LEFT) . ' — Trabajo Completado',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.parte-completado',
            with: [
                'parte'      => $this->parte,
                'order'      => $this->parte->workOrder,
                'customer'   => $this->parte->workOrder->customer,
                'technician' => $this->parte->technician,
            ]
        );
    }
}
