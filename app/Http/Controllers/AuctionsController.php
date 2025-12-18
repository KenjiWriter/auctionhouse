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
            'categories' => $this->getHierarchicalCategories(),
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

    public function bid(Request $request, int $auctionId, \App\Services\BiddingService $biddingService)
    {
        try {
            $biddingService->placeBid($auctionId, $request->user()->id, (float)$request->input('amount'));
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('success', __('auction.bid_success'));
    }

    public function setAutoBid(Request $request, int $auctionId)
    {
        $validated = $request->validate([
            'max_amount' => 'required|numeric|min:0',
            'is_active' => 'required|boolean',
        ]);

        $auction = Auction::findOrFail($auctionId);

        if ($auction->user_id === $request->user()->id) {
            return back()->withErrors(['auto_bid' => __('validation.owner_cannot_bid')]);
        }

        $autoBid = \App\Models\AutoBid::updateOrCreate(
            ['auction_id' => $auctionId, 'user_id' => $request->user()->id],
            ['max_amount' => $validated['max_amount'], 'is_active' => $validated['is_active']]
        );

        // If newly activated, try to resolve it immediately if the user is outbid
        if ($autoBid->is_active) {
            $service = app(\App\Services\BiddingService::class);
            $service->resolveAutoBids($auction);
        }

        return back()->with('success', __('auction.auto_bid_updated'));
    }

    public function index(Request $request)
    {
        $auctions = Auction::with('category', 'user', 'images')
            ->withIsWatched()
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($request->category, function ($query, $category) {
                $query->where('category_id', $category);
            })
            ->when($request->min_price, function ($query, $min) {
                $query->where(function($q) use ($min) {
                    $q->where('current_price', '>=', $min)
                      ->orWhere(function($sub) use ($min) {
                          $sub->whereNull('current_price')
                              ->where('starting_price', '>=', $min);
                      });
                });
            })
            ->when($request->max_price, function ($query, $max) {
                 $query->where(function($q) use ($max) {
                    $q->where('current_price', '<=', $max)
                      ->orWhere(function($sub) use ($max) {
                          $sub->whereNull('current_price')
                              ->where('starting_price', '<=', $max);
                      });
                });
            })
            ->when($request->buy_now === 'true', function ($query) {
                $query->whereNotNull('buy_now_price');
            })
            ->when($request->status, function ($query, $status) {
                // If status specific requested, allow it, otherwise default to active/upcoming
                $query->where('status', $status);
            }, function ($query) {
                 $query->whereIn('status', [Auction::STATUS_ACTIVE, Auction::STATUS_UPCOMING]);
            })
            ->orderByRaw("FIELD(status, 'active', 'upcoming')") // Prioritize active
            ->orderBy('ends_at', 'asc') // Ending soonest first
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Auctions/Index', [
            'auctions' => $auctions,
            'filters' => $request->only(['search', 'category', 'min_price', 'max_price', 'buy_now', 'status']),
            'categories' => $this->getHierarchicalCategories(),
        ]);
    }

    public function show(Auction $auction)
    {
        $auction->load(['category', 'user', 'images', 'bids.user']);
        
        // Load winner for ended auctions
        if ($auction->status === Auction::STATUS_ENDED && $auction->winner_id) {
            $auction->load('winner');
        }
        
        $autoBid = null;
        // Load is_watched for single auction
        if ($user = request()->user()) {
            $auction->loadExists(['watchers as is_watched' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            }]);

            $autoBid = \App\Models\AutoBid::where('auction_id', $auction->id)
                ->where('user_id', $user->id)
                ->first();
        }

        return Inertia::render('Auctions/Show', [
            'auction' => $auction,
            'userAutoBid' => $autoBid,
        ]);
    }

    public function myAuctions(Request $request)
    {
        $auctions = $request->user()->auctions()
            ->with('category', 'user', 'images')
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
            ->with('category', 'user', 'images')
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
            'categories' => $this->getHierarchicalCategories(),
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
            ->with(['category', 'user', 'images'])
            ->withIsWatched() // Should be true for all, but consistent
            ->latest()
            ->paginate(10); // Or whatever pagination size

        return Inertia::render('Auctions/Index', [
            'auctions' => $auctions,
            'title' => 'Watchlist',
        ]);
    }
    private function getHierarchicalCategories()
    {
        $categories = \App\Models\Category::with('parent.parent')->get(); // Eager load up to 2 levels of parents for 3-level depth

        return $categories->map(function ($category) {
            $path = [];
            $current = $category;
            
            while ($current) {
                array_unshift($path, $current->name);
                $current = $current->parent;
            }

            return [
                'id' => $category->id,
                'name' => implode(' > ', $path),
            ];
        })->sortBy('name')->values();
    }

    public function markNotified(Request $request, Auction $auction)
    {
        // Only the seller can mark as notified
        if ($request->user()->id !== $auction->user_id) {
            abort(403);
        }

        $auction->markSellerNotified();

        return back();
    }
}
