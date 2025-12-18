<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Auction;
use App\Models\Bid;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function show(Request $request, User $user = null)
    {
        // If no user provided, show authenticated user's profile
        $user = $user ?? $request->user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        $isOwner = $request->user() && $request->user()->id === $user->id;

        // Calculate stats
        $stats = [
            'total_auctions' => $user->auctions()->count(),
            'auctions_won' => Auction::where('winner_id', $user->id)->count(),
            'active_auctions' => $user->auctions()
                ->whereIn('status', [Auction::STATUS_ACTIVE, Auction::STATUS_UPCOMING])
                ->count(),
            'joined' => $user->created_at->format('F Y'),
        ];

        // Default tab data - public auctions
        $auctions = $user->auctions()
            ->with(['category', 'images'])
            ->whereIn('status', [Auction::STATUS_ACTIVE, Auction::STATUS_UPCOMING])
            ->latest()
            ->paginate(12);

        return Inertia::render('Profile/Show', [
            'profileUser' => $user,
            'isOwner' => $isOwner,
            'stats' => $stats,
            'auctions' => $auctions,
        ]);
    }

    public function bidding(Request $request)
    {
        $user = $request->user();

        // Get auctions where user has active bids
        $biddingAuctions = Auction::whereHas('bids', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->whereIn('status', [Auction::STATUS_ACTIVE, Auction::STATUS_UPCOMING])
        ->with(['category', 'images', 'bids' => function ($q) {
            $q->orderByDesc('amount')->limit(1);
        }])
        ->get()
        ->map(function ($auction) use ($user) {
            // Determine if user is leading
            $highestBid = $auction->bids->first();
            $userBid = $auction->bids()->where('user_id', $user->id)->orderByDesc('amount')->first();
            
            return [
                'auction' => $auction,
                'is_leading' => $highestBid && $highestBid->user_id === $user->id,
                'user_bid_amount' => $userBid?->amount,
                'current_price' => $auction->current_price ?? $auction->starting_price,
            ];
        });

        return Inertia::render('Profile/Bidding', [
            'bidding' => $biddingAuctions,
        ]);
    }

    public function wins(Request $request)
    {
        $user = $request->user();

        $wins = Auction::where('winner_id', $user->id)
            ->with(['category', 'images', 'user'])
            ->latest('ends_at')
            ->paginate(12);

        return Inertia::render('Profile/Wins', [
            'wins' => $wins,
        ]);
    }
}
