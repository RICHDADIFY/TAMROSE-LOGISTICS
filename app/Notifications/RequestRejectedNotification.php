<?php

namespace App\Notifications;

use App\Models\TripRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage; // ← add this

class RequestRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(public TripRequest $request, public ?string $reason = null) {}

    public function via($notifiable): array
    {
        return ['mail', 'database']; // ← enable email + DB
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Ride Request #{$this->request->id} Rejected")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your ride request #{$this->request->id} was rejected.");

        if ($this->reason) {
            $mail->line("Reason: {$this->reason}");
        }

        return $mail
            ->action('View Request', route('ride-requests.show', $this->request->id))
            ->line('If you have questions, kindly contact Operations.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'        => 'request.rejected',
            'request_id'  => $this->request->id,
            'title'       => 'Ride Request Rejected',
            'message'     => "Your ride request #{$this->request->id} was rejected."
                              . ($this->reason ? " Reason: {$this->reason}" : ''),
            'status'      => $this->request->status,
            'link'        => route('ride-requests.show', $this->request->id),
        ];
    }
}
