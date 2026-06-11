<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BroadcastMessage extends Notification
{
    use Queueable;

    public function __construct(public string $subject, public string $body)
    {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->line($this->body)
            ->action('Open Dashboard', url('/member'))
            ->line('Thank you!');
    }
}
