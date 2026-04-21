<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemoRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Demo Request from ' . $this->data['first_name'] . ' ' . $this->data['last_name'],
            replyTo: [$this->data['email']],
        );
    }

    public function build()
    {
        $logoPath = public_path('images/digidittos-logo-email.png');

        return $this->view('emails.demo-request')
            ->with([
                'data' => $this->data,
                'logoPath' => $logoPath,
            ]);
    }
}
