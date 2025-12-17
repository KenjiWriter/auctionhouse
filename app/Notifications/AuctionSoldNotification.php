<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AuctionSoldNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $auction;
    public $winner;

    public function __construct($auction, $winner)
    {
        $this->auction = $auction;
        $this->winner = $winner;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your auction was sold: ' . $this->auction->title)
            ->line('Your auction "' . $this->auction->title . '" has ended and was sold!')
            ->line('Sold Price: $' . number_format($this->auction->current_price, 2))
            ->line('Winner: ' . $this->winner->name . ' (' . $this->winner->email . ')')
            ->action('View Auction', route('auctions.show', $this->auction->id));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
