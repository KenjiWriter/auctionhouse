<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Auction;
use App\Models\Bid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        ->with(['category', 'images', 'user', 'bids' => function ($q) {
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

    public function edit(Request $request)
    {
        $settings = \App\Models\UserNotificationSetting::firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'watch_start_offsets' => \App\Models\UserNotificationSetting::DEFAULT_START_OFFSETS,
                'watch_end_offsets' => \App\Models\UserNotificationSetting::DEFAULT_END_OFFSETS,
                'enable_in_app' => true,
                'enable_email' => false,
            ]
        );

        return Inertia::render('Profile/Edit', [
            'user' => $request->user(),
            'notificationSettings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|max:2048',
            'avatar_preset' => 'nullable|string',
        ]);

        $user = $request->user();
        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
        ]);

        if ($request->hasFile('avatar')) {
             if ($user->avatar_path) {
                 Storage::disk('public')->delete($user->avatar_path);
             }
            $path = $request->file('avatar')->storePublicly(
                'avatars/' . $user->id,
                ['disk' => 'public']
            );
            $user->update([
                'avatar_path' => $path,
                'avatar_preset' => null,
            ]);
        } elseif ($request->avatar_preset) {
             if ($user->avatar_path) {
                 Storage::disk('public')->delete($user->avatar_path);
             }
             $user->update([
                 'avatar_path' => null,
                 'avatar_preset' => $request->avatar_preset,
             ]);
        }
        
        return redirect()->route('profile.mine');
    }

    public function updateNotificationSettings(Request $request)
    {
        $validated = $request->validate([
            'watch_start_offsets' => 'nullable|array',
            'watch_start_offsets.*' => 'integer|min:1',
            'watch_end_offsets' => 'nullable|array',
            'watch_end_offsets.*' => 'integer|min:1',
            'enable_in_app' => 'boolean',
            'enable_email' => 'boolean',
        ]);

        $settings = \App\Models\UserNotificationSetting::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return back()->with('success', 'Notification settings updated successfully.');
    }
}
