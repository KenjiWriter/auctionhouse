<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Conversation;
use App\Models\Auction;
use App\Models\Message;

class ConversationController extends Controller
{
    // Start a conversation (or find existing)
    public function store(Request $request)
    {
        $request->validate([
            'auction_id' => 'required|exists:auctions,id',
        ]);
        
        $auction = Auction::findOrFail($request->auction_id);
        $user = $request->user();
        
        // Prevent chatting with self
        if ($auction->user_id === $user->id) {
             return back()->withErrors(['message' => 'You cannot chat with yourself.']);
        }

        // Find existing conversation between these users for this auction
        $conversation = Conversation::firstOrCreate(
            [
                'auction_id' => $auction->id,
                'buyer_id' => $user->id,
                'seller_id' => $auction->user_id,
            ]
        );

        return redirect()->route('conversations.show', $conversation->id);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $conversations = Conversation::where('buyer_id', $user->id)
            ->orWhere('seller_id', $user->id)
            ->with(['auction', 'lastMessage', 'buyer', 'seller'])
            ->withCount(['messages as unread_count' => function ($query) use ($user) {
                $query->where('user_id', '!=', $user->id)
                      ->whereNull('read_at');
            }])
            ->latest('updated_at')
            ->get();
            
        return Inertia::render('Conversations/Index', [
            'conversations' => $conversations
        ]);
    }

    public function show(Request $request, Conversation $conversation)
    {
        // policy check (simple)
        if ($conversation->buyer_id !== $request->user()->id && $conversation->seller_id !== $request->user()->id) {
            abort(403);
        }

        // Mark unread messages as read
        $conversation->messages()
            ->where('user_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        
        $conversation->load(['messages.user', 'auction.category', 'buyer', 'seller']);
        
        return Inertia::render('Conversations/Show', [
            'conversation' => $conversation
        ]);
    }
    
    public function sendMessage(Request $request, Conversation $conversation)
    {
         if ($conversation->buyer_id !== $request->user()->id && $conversation->seller_id !== $request->user()->id) {
            abort(403);
        }
        
        $request->validate(['content' => 'required|string']);
        
        $message = $conversation->messages()->create([
            'user_id' => $request->user()->id,
            'content' => $request->input('content'),
        ]);
        
        $conversation->touch(); // Updated at for sorting
        
        // Broadcast event here later
        \App\Events\MessageSent::dispatch($message);
        
        return back();
    }
}
