<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EndAuctions implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $auctions = \App\Models\Auction::active()
            ->where('ends_at', '<=', now())
            ->get();

        foreach ($auctions as $auction) {
            // Check for bids
            $winningBid = $auction->bids()->first(); // Ordered by amount desc in model

            if ($winningBid) {
                $auction->update([
                    'status' => \App\Models\Auction::STATUS_ENDED,
                    'winner_id' => $winningBid->user_id,
                    'post_status' => \App\Models\Auction::POST_STATUS_AWAITING_CONTACT,
                ]);

                // Dispatch Event for Email Notification
                \App\Events\AuctionEnded::dispatch($auction);

                // Notify seller to contact winner
                $seller = $auction->user;
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->notify(
                    $seller,
                    'auction_won_action_required',
                    $auction->id,
                    [
                        'auction_title' => $auction->title,
                        'winner_name' => $winningBid->user->name,
                    ]
                );
            } else {
                $auction->update([
                    'status' => \App\Models\Auction::STATUS_ENDED_WITHOUT_SALE,
                ]);
            }
        }
    }
}
