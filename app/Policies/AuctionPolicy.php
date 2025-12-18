<?php

namespace App\Policies;

use App\Models\Auction;
use App\Models\User;

class AuctionPolicy
{
    /**
     * Determine if seller can mark that they contacted the winner
     */
    public function contactWinner(User $user, Auction $auction): bool
    {
        return $user->id === $auction->user_id 
            && $auction->status === Auction::STATUS_ENDED
            && $auction->post_status === Auction::POST_STATUS_AWAITING_CONTACT
            && $auction->winner_id !== null;
    }

    /**
     * Determine if seller can mark payment as received
     */
    public function markPaymentReceived(User $user, Auction $auction): bool
    {
        return $user->id === $auction->user_id 
            && $auction->status === Auction::STATUS_ENDED
            && $auction->post_status === Auction::POST_STATUS_AWAITING_PAYMENT;
    }

    /**
     * Determine if user can mark transaction as completed
     */
    public function markCompleted(User $user, Auction $auction): bool
    {
        $isSeller = $user->id === $auction->user_id;
        $isWinner = $user->id === $auction->winner_id;

        return ($isSeller || $isWinner)
            && $auction->status === Auction::STATUS_ENDED
            && in_array($auction->post_status, [
                Auction::POST_STATUS_AWAITING_PAYMENT,
                Auction::POST_STATUS_AWAITING_CONTACT,
            ]);
    }

    /**
     * Determine if user can open a dispute
     */
    public function openDispute(User $user, Auction $auction): bool
    {
        $isSeller = $user->id === $auction->user_id;
        $isWinner = $user->id === $auction->winner_id;

        return ($isSeller || $isWinner)
            && $auction->status === Auction::STATUS_ENDED
            && $auction->post_status !== Auction::POST_STATUS_COMPLETED
            && $auction->post_status !== Auction::POST_STATUS_DISPUTED;
    }

    /**
     * Determine if seller can cancel the auction
     */
    public function cancelAuction(User $user, Auction $auction): bool
    {
        // Only seller can cancel
        if ($user->id !== $auction->user_id) {
            return false;
        }

        // Only upcoming auctions
        if ($auction->status !== Auction::STATUS_UPCOMING) {
            return false;
        }

        // No bids placed
        $hasBids = $auction->bids()->exists();
        return !$hasBids;
    }
}
