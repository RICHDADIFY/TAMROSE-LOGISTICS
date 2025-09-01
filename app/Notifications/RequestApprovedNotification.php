<?php

namespace App\Notifications;

use App\Models\TripRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;   // <-- UNCOMMENT/ADD
// use Illuminate\Contracts\Queue\ShouldQueue;        // (optional) add if you want to queue

class RequestApprovedNotification extends Notification /* implements ShouldQueue */
{
    use Queueable;

    public function __construct(public TripRequest $request) {}

    public function via($notifiable): array
    {
        return ['mail', 'database']; // <-- ENABLE MAIL
    }

    public function toMail($notifiable): MailMessage   // <-- ADD THIS METHOD
    {
        return (new MailMessage)
            ->subject("Ride Request #{$this->request->id} Approved")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your ride request #{$this->request->id} has been approved.")
            ->action('View Request', route('ride-requests.show', $this->request->id))
            ->line('Thank you for using Tamrose Logistics!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'        => 'request.approved',
            'request_id'  => $this->request->id,
            'title'       => 'Ride Request Approved',
            'message'     => "Your ride request #{$this->request->id} has been approved.",
            'status'      => $this->request->status,
            'approved_at' => optional($this->request->approved_at)?->toIso8601String(),
            'approved_by' => $this->request->approved_by,
            'link'        => route('ride-requests.show', $this->request->id),
        ];
    }
}
