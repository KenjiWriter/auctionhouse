<?php

namespace App\Services;

use App\Models\Auction;
use App\Models\Bid;
use App\Models\AutoBid;
use App\Events\BidPlaced;
use App\Events\Outbid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BiddingService
{
    /**
     * Place a bid on an auction.
     * 
     * @param Auction|int $auction
     * @param int $userId
     * @param float $amount
     * @return Bid
     */
    public function placeBid($auction, int $userId, float $amount)
    {
        if (is_numeric($auction)) {
            $auction = Auction::findOrFail($auction);
        }

        return DB::transaction(function () use ($auction, $userId, $amount) {
            // Lock the auction row
            $auction = Auction::where('id', $auction->id)->lockForUpdate()->first();

            $this->validateBid($auction, $userId, $amount);

            $oldHighestBid = $auction->bids()->first();

            // Create the bid
            $bid = Bid::create([
                'auction_id' => $auction->id,
                'user_id' => $userId,
                'amount' => $amount,
            ]);

            // Update auction price
            $auction->update(['current_price' => $amount]);

            // Dispatch event
            event(new BidPlaced($bid));

            // Notify outbid user
            if ($oldHighestBid && $oldHighestBid->user_id !== $userId) {
                event(new Outbid($auction->id, $oldHighestBid->user_id, $amount));
            }

            // Trigger proxy bidding
            $this->resolveAutoBids($auction);

            return $bid;
        });
    }

    /**
     * Resolve proxy bids for an auction after a new bid is placed.
     */
    public function resolveAutoBids(Auction $auction)
    {
        // We are already inside a transaction and have the lock.
        $highestBid = $auction->bids()->first();
        if (!$highestBid) return;

        // Find the best auto-bid that isn't the current leader
        $bestAutoBid = AutoBid::where('auction_id', $auction->id)
            ->where('is_active', true)
            ->where('user_id', '!=', $highestBid->user_id)
            ->orderByDesc('max_amount')
            ->first();

        if (!$bestAutoBid) return;

        $nextRequiredBid = $highestBid->amount + 1.00;

        // If the best auto-bidder can outbid the current leader
        if ($bestAutoBid->max_amount >= $nextRequiredBid) {
            // Check if there are OTHER auto-bidders who might compete
            $secondBestAutoBid = AutoBid::where('auction_id', $auction->id)
                ->where('is_active', true)
                ->where('user_id', '!=', $bestAutoBid->user_id)
                ->where('user_id', '!=', $highestBid->user_id)
                ->orderByDesc('max_amount')
                ->first();

            $finalAmount = $nextRequiredBid;

            if ($secondBestAutoBid && $secondBestAutoBid->max_amount >= $nextRequiredBid) {
                // They compete. The best one wins at 1.00 above second best's max, or his own max.
                $finalAmount = min($bestAutoBid->max_amount, $secondBestAutoBid->max_amount + 1.00);
            }

            // Place the auto-bid
            $bid = Bid::create([
                'auction_id' => $auction->id,
                'user_id' => $bestAutoBid->user_id,
                'amount' => $finalAmount,
            ]);

            $auction->update(['current_price' => $finalAmount]);
            event(new BidPlaced($bid));

            // Notify the user who just got outbid (which was the leader before this resolution)
            event(new Outbid($auction->id, $highestBid->user_id, $finalAmount));
            
            // Note: Since we jumped to the final competitive price, we don't need to recurse.
        }
    }

    protected function validateBid(Auction $auction, int $userId, float $amount)
    {
        if ($auction->status !== Auction::STATUS_ACTIVE) {
            throw ValidationException::withMessages(['amount' => __('validation.auction_not_active')]);
        }

        if ($auction->user_id === $userId) {
            throw ValidationException::withMessages(['amount' => __('validation.owner_cannot_bid')]);
        }

        $minAmount = $auction->bids()->count() > 0 
            ? (float)$auction->current_price + 1.00 
            : (float)$auction->starting_price;

        if ($amount < $minAmount) {
            throw ValidationException::withMessages(['amount' => __('validation.bid_too_low')]);
        }
    }
}
