<?php

namespace App\Notifications\Students;

use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeStudent extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Team $team,
        public readonly string $temporaryPassword,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Selamat datang di {$this->team->name}")
            ->line("Akun Anda telah dibuat oleh administrator {$this->team->name}.")
            ->line("Email: {$notifiable->email}")
            ->line("Password sementara: {$this->temporaryPassword}")
            ->action('Login Sekarang', url('/'))
            ->line('Segera ganti password Anda setelah login.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'team_id' => $this->team->id,
            'team_name' => $this->team->name,
        ];
    }
}
