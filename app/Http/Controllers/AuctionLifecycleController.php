<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use Illuminate\Http\Request;
use App\Services\NotificationService;

class AuctionLifecycleController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Seller marks that they contacted the winner
     */
    public function contactWinner(Request $request, Auction $auction)
    {
        $this->authorize('contactWinner', $auction);

        $auction->update([
            'seller_contacted_at' => now(),
            'post_status' => Auction::POST_STATUS_AWAITING_PAYMENT,
        ]);

        return back()->with('success', 'Winner contact status updated.');
    }

    /**
     * Seller confirms payment was received
     */
    public function markPaymentReceived(Request $request, Auction $auction)
    {
        $this->authorize('markPaymentReceived', $auction);

        $auction->update([
            'seller_confirmed_at' => now(),
            'post_status' => Auction::POST_STATUS_COMPLETED,
        ]);

        return back()->with('success', 'Payment confirmed. Transaction complete!');
    }

    /**
     * Either party marks transaction as completed
     */
    public function markCompleted(Request $request, Auction $auction)
    {
        $this->authorize('markCompleted', $auction);

        $isWinner = $request->user()->id === $auction->winner_id;

        $auction->update([
            $isWinner ? 'buyer_confirmed_at' : 'seller_confirmed_at' => now(),
            'post_status' => Auction::POST_STATUS_COMPLETED,
        ]);

        return back()->with('success', 'Transaction marked as completed!');
    }

    /**
     * Open a dispute
     */
    public function openDispute(Request $request, Auction $auction)
    {
        $this->authorize('openDispute', $auction);

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $auction->update([
            'disputed_at' => now(),
            'dispute_reason' => $validated['reason'],
            'post_status' => Auction::POST_STATUS_DISPUTED,
        ]);

        // Notify both parties
        $seller = $auction->user;
        $winner = $auction->winner;

        if ($request->user()->id === $seller->id) {
            // Seller opened dispute, notify winner
            $this->notificationService->notify(
                $winner,
                'dispute_opened',
                $auction->id,
                [
                    'auction_title' => $auction->title,
                    'opened_by' => 'seller',
                ]
            );
        } else {
            // Winner opened dispute, notify seller
            $this->notificationService->notify(
                $seller,
                'dispute_opened',
                $auction->id,
                [
                    'auction_title' => $auction->title,
                    'opened_by' => 'winner',
                ]
            );
        }

        return back()->with('success', 'Dispute opened. Support will review this case.');
    }

    /**
     * Cancel an upcoming auction (no bids)
     */
    public function cancelAuction(Request $request, Auction $auction)
    {
        $this->authorize('cancelAuction', $auction);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $auction->update([
            'cancelled_at' => now(),
            'cancel_reason' => $validated['reason'] ?? null,
            'status' => Auction::STATUS_CANCELLED,
            'post_status' => Auction::POST_STATUS_CANCELLED,
        ]);

        return redirect()->route('auctions.mine')->with('success', 'Auction cancelled successfully.');
    }
}
