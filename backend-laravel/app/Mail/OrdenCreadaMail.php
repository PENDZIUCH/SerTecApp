<?php

namespace App\Mail;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrdenCreadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public WorkOrder $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Orden de Servicio #{$this->order->id} — Confirmación",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orden-creada',
            with: [
                'order' => $this->order,
                'customer' => $this->order->customer,
                'technician' => $this->order->assignedTech,
            ]
        );
    }
}
