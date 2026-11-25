<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

// Sent to the contact email (Admin > Settings) when a customer uses the contact page.
class ContactMessageMail extends Mailable
{
    public function __construct(public ContactMessage $contactMessage)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            // "Reply" in the mail client answers the customer directly.
            replyTo: [new Address($this->contactMessage->email, $this->contactMessage->name)],
            subject: 'Kontakt: ' . $this->contactMessage->subject . ' - ShopModa',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-message',
        );
    }
}
