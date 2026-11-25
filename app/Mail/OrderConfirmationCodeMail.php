<?php

namespace App\Mail;

use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

// Sent right after checkout: the code and the link to confirm the order.
// $reminder = true: the same mail with a new code, sent when the order is still unconfirmed a day later (orders:remind).
class OrderConfirmationCodeMail extends Mailable
{
    public function __construct(
        public Order $order,
        public string $code,
        public string $link,
        public CarbonInterface $expiresAt,
        public bool $reminder = false,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->reminder ? 'Podsetnik: porudžbina ' . $this->order->number . ' još nije potvrđena' : 'Potvrdite porudžbinu ' . $this->order->number) . ' - ShopModa',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmation-code',
        );
    }
}
