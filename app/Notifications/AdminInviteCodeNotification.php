<?php

namespace App\Notifications;

use App\Models\AdminInviteCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminInviteCodeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public AdminInviteCode $invite, public bool $copy = false) {}

    public function via($notifiable) { return ['mail']; }

    public function toMail($notifiable)
    {
        $url = url('/register?role='.urlencode($this->invite->role).'&code='.$this->invite->code);

        return (new MailMessage)
            ->subject('Admin Invite Code — '.$this->invite->role)
            ->greeting($this->copy ? 'Copy of your invite code' : 'Your admin invite code')
            ->line('Role: '.$this->invite->role)
            ->line('Code: **'.$this->invite->code.'**')
            ->line('Expires: '.($this->invite->expires_at?->toDayDateTimeString() ?? 'never'))
            ->line('Uses: '.($this->invite->max_uses ?? 'unlimited'))
            ->when($this->invite->notes, fn($m)=>$m->line('Notes: '.$this->invite->notes))
            ->action('Open registration', $url)
            ->line('Or paste this URL: '.$url);
    }
}
