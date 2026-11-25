<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewOrderConfirmationMail extends Mailable
{
    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vaša porudžbina ' . $this->order->number . ' je potvrđena - ShopModa',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-confirmation-order',
        );
    }

}
