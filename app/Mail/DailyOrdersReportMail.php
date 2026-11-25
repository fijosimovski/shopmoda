<?php

namespace App\Mail;

use Carbon\CarbonInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

// Sent at midnight by orders:daily-report to the order email (Admin > Settings): the day's orders + the Excel file.
class DailyOrdersReportMail extends Mailable
{
    public function __construct(
        public Collection $orders,
        public CarbonInterface $day,
        public string $xlsxPath,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Porudžbine za ' . $this->day->format('d.m.Y.') . ': ' . $this->orders->count() . ' - ShopModa',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-orders-report',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->xlsxPath)
                ->as('shopmoda-porudzbine-' . $this->day->format('Y-m-d') . '.xlsx')
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}
