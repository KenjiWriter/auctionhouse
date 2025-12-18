<?php

namespace App\Listeners;

use App\Events\Outbid;
use App\Models\User;
use App\Models\Auction;
use App\Models\AutoBid;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOutbidNotification
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(Outbid $event): void
    {
        $user = User::find($event->userId);
        if (!$user) return;

        $auction = Auction::find($event->auctionId);
        if (!$auction) return;

        // Check if user has auto-bid and if it's maxed out
        $autoBid = AutoBid::where('auction_id', $event->auctionId)
            ->where('user_id', $event->userId)
            ->where('is_active', true)
            ->first();

        if ($autoBid && $event->newPrice > $autoBid->max_amount) {
            // User has auto-bid but it's maxed out
            $this->notificationService->notify(
                $user,
                'auction_autobid_max_reached',
                $event->auctionId,
                [
                    'current_price' => $event->newPrice,
                    'max_amount' => $autoBid->max_amount,
                    'auction_title' => $auction->title,
                ]
            );
        } else {
            // Regular outbid notification
            $this->notificationService->notify(
                $user,
                'auction_outbid',
                $event->auctionId,
                [
                    'current_price' => $event->newPrice,
                    'auction_title' => $auction->title,
                    'has_autobid' => $autoBid ? true : false,
                    'max_amount' => $autoBid?->max_amount,
                ]
            );
        }
    }
}
