<?php

namespace App\Notifications;

use App\Models\Tenants\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DomainCreated extends Notification implements ShouldQueue
{
    use Queueable;

    private string $domain;

    public function __construct(string $domain)
    {
        $this->domain = $domain;
    }

    public function via(User $notifiable)
    {
        return ['mail'];
    }

    public function toMail(User $notifiable)
    {
        return (new MailMessage)
            ->line('Selamat datang di zonaKasir')
            ->line('Terima kasih telah menggunakan aplikasi kami!')
            ->line('Kami telah membuatkan domain untuk anda, silahkan daftarkan domain '.$this->domain.' ke aplikasi di menu domain')
            ->line('dan domain anda akan aktif dalam waktu 30 hari untuk masa percobaan')
            ->salutation('zonaKasir');
    }

    public function toArray(User $notifiable)
    {
        return [
            //
        ];
    }
}
