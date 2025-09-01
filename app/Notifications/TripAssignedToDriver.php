<?php

namespace App\Notifications;

use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TripAssignedToDriver extends Notification
{
    use Queueable;

    public function __construct(public Trip $trip) {}

    public function via($notifiable): array
    {
        // email + database for the driver
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $v = $this->trip->vehicle;
        $vehicleLabel = $v?->display_label ?? $v?->label ?? "Vehicle #{$this->trip->vehicle_id}";
        return (new MailMessage)
            ->subject("New Trip Assigned (Trip #{$this->trip->id})")
            ->greeting("Hello {$notifiable->name},")
            ->line("You've been assigned to Trip #{$this->trip->id}.")
            ->line("Vehicle: {$vehicleLabel}")
            ->line('Depart: ' . optional($this->trip->depart_at)->format('Y-m-d H:i'))
            ->line('Return: ' . optional($this->trip->return_at)->format('Y-m-d H:i'))
            ->action('View Trip', route('trips.show', $this->trip->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'type'       => 'trip.assigned',
            'trip_id'    => $this->trip->id,
            'driver_id'  => $this->trip->driver_id,
            'vehicle_id' => $this->trip->vehicle_id,
            'depart_at'  => optional($this->trip->depart_at)->toIso8601String(),
            'return_at'  => optional($this->trip->return_at)->toIso8601String(),
            'link'       => route('trips.show', $this->trip->id),
        ];
    }
}
