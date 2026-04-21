<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Branded email sent to the DigiDittos team whenever a visitor submits
 * the /contact form. Mirrors the old Node/Mailtrap template (dark
 * teal-accented DigiDittos styling) but delivered through Laravel's
 * Mail facade so it picks up the SMTP credentials from `.env`.
 */
class ContactRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        $email = $this->data['email'] ?? null;
        $name = $this->data['name'] ?? 'Visitor';
        $service = $this->data['service'] ?? 'General';

        return new Envelope(
            subject: "New Contact: {$name} — {$service}",
            replyTo: $email ? [new Address($email, $name)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-request',
            with: [
                'data' => $this->data,
                'submittedAt' => now()->format('l, F j, Y \a\t g:i A'),
                'logoUrl' => env('MAIL_LOGO_URL', 'https://www.digidittos.com/images/logo.png'),
                'siteUrl' => env('MAIL_SITE_URL', 'https://www.digidittos.com'),
            ],
        );
    }
}
