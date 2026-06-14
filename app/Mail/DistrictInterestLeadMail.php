<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Уведомление менеджеру о заявке «Интерес к району» с карты на главной.
 */
class DistrictInterestLeadMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $clientName,
        public string $phone,
        public string $districtTitle,
        public string $districtType,
        public string $districtId,
        public ?string $page = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Заявка по району: '.$this->districtTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.district-interest-lead',
        );
    }
}
