<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Auction;
use App\Models\Category;

class AuctionsController extends Controller
{
    public function create()
    {
        return Inertia::render('Auctions/Create', [
            'categories' => \App\Models\Category::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string',
            'starting_price' => 'required|numeric|min:0',
            'buy_now_price' => 'nullable|numeric|gt:starting_price',
            'starts_at' => 'nullable|date|after_or_equal:now',
            'ends_at' => 'required|date|after:starts_at',
            'images.*' => 'image|max:2048', // 2MB max
        ]);

        $auction = $request->user()->auctions()->create($validated);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('auctions', 'public');
                $auction->images()->create([
                    'path' => $path,
                    'order' => $index,
                ]);
            }
        }
        
        // Determine initial status
        $status = Auction::STATUS_ACTIVE;
        if ($auction->starts_at && $auction->starts_at->isFuture()) {
            $status = Auction::STATUS_UPCOMING;
        }
        
        $auction->update(['status' => $status]);

        return redirect()->route('auctions.index')->with('success', 'Auction created successfully.');
    }

    public function bid(Request $request, int $auctionId)
    {
        $user = $request->user();
        
        // 1. Transaction & Lock
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request, $auctionId, $user) {
                // Lock the auction row for update to prevent race conditions
                $auction = Auction::lockForUpdate()->findOrFail($auctionId);

                // 2. Validate Auction Status
                if ($auction->status !== Auction::STATUS_ACTIVE) {
                    throw new \Exception(__('validation.auction_not_active'));
                }

                if ($auction->ends_at && $auction->ends_at->isPast()) {
                     throw new \Exception(__('validation.auction_ended'));
                }

                // 3. Validate User (Owner Check)
                if ($auction->user_id === $user->id) {
                     throw new \Exception(__('validation.owner_cannot_bid'));
                }

                // 4. Validate Bid Amount
                $minBid = ($auction->current_price ?: $auction->starting_price);
                // Optional: enforce step. For now just > current. 
                // However, strictly speaking, if current_price is set, new bid must be > current.
                // If it's the FIRST bid, it must be >= starting_price.
                // Let's refine:
                // If bids exist, must be > current_price.
                // If no bids, must be >= starting_price.
                
                $currentHighest = $auction->current_price;
                $amount = $request->input('amount');

                if ($currentHighest) {
                    if ($amount <= $currentHighest) {
                        throw new \Exception(__('validation.bid_too_low', ['min' => $currentHighest]));
                    }
                } else {
                    if ($amount < $auction->starting_price) {
                         throw new \Exception(__('validation.bid_too_low', ['min' => $auction->starting_price]));
                    }
                }

                $previousHighestBid = $auction->bids()->first(); // Only works if ordered by desc in model relation

                // 5. Place Bid
                $bid = $auction->bids()->create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                ]);

                // 6. Update Auction
                $auction->update(['current_price' => $amount]);

                // 7. Events
                \App\Events\BidPlaced::dispatch($bid);

                // Notify previous bidder
                if ($previousHighestBid && $previousHighestBid->user_id !== $user->id) {
                    \App\Events\Outbid::dispatch($auction->id, $previousHighestBid->user_id, $amount);
                }
            });
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('success', __('auction.bid_success'));
    }

    public function index(Request $request)
    {
        $auctions = Auction::with('category', 'user')
            ->withIsWatched()
            ->whereIn('status', [Auction::STATUS_ACTIVE, Auction::STATUS_UPCOMING])
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($request->category, function ($query, $category) {
                $query->where('category_id', $category);
            })
            ->orderByRaw("FIELD(status, 'active', 'upcoming')") // Prioritize active
            ->orderBy('ends_at', 'asc') // Ending soonest first
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Auctions/Index', [
            'auctions' => $auctions,
            'filters' => $request->only(['search', 'category']),
            'categories' => \App\Models\Category::all(),
        ]);
    }

    public function show(Auction $auction)
    {
        $auction->load(['category', 'user', 'images', 'bids.user']);
        
        // Load is_watched for single auction
        if (request()->user()) {
            $auction->loadExists(['watchers as is_watched' => function ($q) {
                $q->where('user_id', request()->user()->id);
            }]);
        }

        return Inertia::render('Auctions/Show', [
            'auction' => $auction,
        ]);
    }

    public function myAuctions(Request $request)
    {
        $auctions = $request->user()->auctions()
            ->with('category', 'user')
            ->withIsWatched()
            ->latest()
            ->paginate(10);

        return Inertia::render('Auctions/Index', [
            'auctions' => $auctions,
            'title' => 'My Auctions',
        ]);
    }

    public function myWins(Request $request)
    {
        $auctions = Auction::where('winner_id', $request->user()->id)
            ->with('category', 'user')
            ->withIsWatched()
            ->latest()
            ->paginate(10);

        return Inertia::render('Auctions/Index', [
            'auctions' => $auctions,
            'title' => 'My Wins',
        ]);
    }

    public function relist(Request $request, Auction $auction)
    {
        if ($request->user()->id !== $auction->user_id) {
            abort(403);
        }

        // Return Create view but with pre-filled data
        // Note: Inertia::render('Auctions/Create', props) will allow us to default the form in Vue
        // OR we can pass a separate prop `relistData`
        
        $relistData = [
            'title' => $auction->title,
            'description' => $auction->description,
            'category_id' => $auction->category_id,
            'starting_price' => $auction->starting_price,
            'buy_now_price' => $auction->buy_now_price,
            // Don't carry over dates or images for now (images safe handling is complex, user can re-upload or we can link if we want)
            // Let's keep it simple: text data copy.
        ];

        return Inertia::render('Auctions/Create', [
            'categories' => \App\Models\Category::all(),
            'relistData' => $relistData, 
        ]);
    }

    public function toggleWatch(Request $request, Auction $auction)
    {
        $user = $request->user();
        $user->watchedAuctions()->toggle($auction->id);

        return back()->with('success', $user->watchedAuctions()->where('auction_id', $auction->id)->exists() ? 'Added to watchlist' : 'Removed from watchlist');
    }

    public function watched(Request $request)
    {
        $auctions = $request->user()->watchedAuctions()
            ->with(['category', 'user'])
            ->withIsWatched() // Should be true for all, but consistent
            ->latest()
            ->paginate(10); // Or whatever pagination size

        return Inertia::render('Auctions/Index', [
            'auctions' => $auctions,
            'title' => 'Watchlist',
        ]);
    }
}
