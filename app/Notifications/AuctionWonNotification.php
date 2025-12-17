<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AuctionWonNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $auction;

    public function __construct($auction)
    {
        $this->auction = $auction;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You won the auction: ' . $this->auction->title)
            ->line('Congratulations! You have won the auction for "' . $this->auction->title . '".')
            ->line('Winning Bid: $' . number_format($this->auction->current_price, 2))
            ->action('View Auction', route('auctions.show', $this->auction->id))
            ->line('Please contact the seller to arrange payment and delivery.');
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
