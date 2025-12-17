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
                ]);

                // Dispatch Event for Email Notification
                \App\Events\AuctionEnded::dispatch($auction);
            } else {
                $auction->update([
                    'status' => \App\Models\Auction::STATUS_ENDED_WITHOUT_SALE,
                ]);
            }
        }
    }
}
