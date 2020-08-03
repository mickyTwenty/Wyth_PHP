<?php

namespace App\Notifications;

use App\Classes\Email;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldQueue;

class CreateAccount extends Notification
{
    /**
     * Complete user object.
     *
     * @var string
     */
    public $user;
    public $request;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user, $request)
    {
        $this->user    = $user;
        $this->request = $request;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mailMessage = (new MailMessage)
            ->subject(Email::makeSubject('Account Credentials'))
            ->greeting($this->user->full_name);

            $mailMessage
                ->line('Welcome to '. constants('global.site.name') . '.')
                ->line('Your account has been created. Your login credentials are below.')
                ->line('Email: ' . $this->user->email)
                ->line('Password: ' . $this->request->password);

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
