<?php

namespace App\Mail;

use App\Classes\Email;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ShareItinerary extends Mailable
{
    use Queueable, SerializesModels;

    public $rideShared;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($rideShared)
    {
        $this->rideShared = $rideShared;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject(Email::makeSubject('Wyth you all the way!'))
            ->markdown('emails.share_itenrary', [
                'rideShared' => $this->rideShared,
                'trip' => $this->rideShared->ride->trip,
                'passenger' => $this->rideShared->passenger,
            ]);
    }
}
