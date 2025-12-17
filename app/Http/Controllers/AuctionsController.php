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
        
        $auction->update(['status' => 'active']); // Or draft depending on logic

        return redirect()->route('auctions.index')->with('success', 'Auction created successfully.');
    }

    public function bid(Request $request, Auction $auction)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|gt:' . ($auction->current_price ?: $auction->starting_price),
        ]);

        if ($auction->user_id === $request->user()->id) {
            return back()->withErrors(['amount' => 'You cannot bid on your own auction.']);
        }

        if ($auction->status !== 'active' || ($auction->ends_at && $auction->ends_at->isPast())) {
            return back()->withErrors(['amount' => 'This auction has ended.']);
        }

        $bid = $auction->bids()->create([
            'user_id' => $request->user()->id,
            'amount' => $validated['amount'],
        ]);

        $auction->update(['current_price' => $validated['amount']]);

        \App\Events\BidPlaced::dispatch($bid);

        return back()->with('success', 'Bid placed successfully!');
    }

    public function index(Request $request)
    {
        $auctions = Auction::with('category', 'user')
            ->where('status', 'active')
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($request->category, function ($query, $category) {
                $query->where('category_id', $category);
            })
            ->latest()
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

        return Inertia::render('Auctions/Show', [
            'auction' => $auction,
        ]);
    }

    public function myAuctions(Request $request)
    {
        $auctions = $request->user()->auctions()
            ->with('category', 'user')
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
            ->latest()
            ->paginate(10);

        return Inertia::render('Auctions/Index', [
            'auctions' => $auctions,
            'title' => 'My Wins',
        ]);
    }
}
