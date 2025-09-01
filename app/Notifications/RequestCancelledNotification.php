<?php

namespace App\Notifications;

use App\Models\TripRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage; // ← add this

class RequestCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(public TripRequest $request, public ?string $note = null) {}

    public function via($notifiable): array
    {
        return ['mail', 'database']; // ← enable email + DB
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Ride Request #{$this->request->id} Cancelled")
            ->greeting("Hello {$notifiable->name},")
            ->line("Ride request #{$this->request->id} has been cancelled.");

        if ($this->note) {
            $mail->line("Note: {$this->note}");
        }

        return $mail
            ->action('View Request', route('ride-requests.show', $this->request->id))
            ->line('This is an automated notification.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'        => 'request.cancelled',
            'request_id'  => $this->request->id,
            'title'       => 'Ride Request Cancelled',
            'message'     => "Your ride request #{$this->request->id} has been cancelled."
                              . ($this->note ? " Note: {$this->note}" : ''),
            'status'      => $this->request->status,
            'link'        => route('ride-requests.show', $this->request->id),
        ];
    }
}
