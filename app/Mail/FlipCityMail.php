<?php

namespace Weboldalnet\FlipCity\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FlipCityMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contact;
    public $mailData;

    public function __construct($contact, array $mailData)
    {
        $this->contact = $contact;
        $this->mailData = $mailData;
    }

    public function build()
    {
        return $this->from(config('app.shop_email'), config('app.shop_name'))
            ->subject($this->mailData['subject'] ?? 'Értesítés')
            ->view('flip-city::mail.notification');
    }
}
