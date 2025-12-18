<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Auction;
use App\Models\User;
use App\Models\Bid;
use App\Events\BidPlaced;

class TestBidEvent extends Command
{
    protected $signature = 'test:bid-event {auctionId}';
    protected $description = 'Manually dispatch BidPlaced event';

    public function handle()
    {
        $auction = Auction::find($this->argument('auctionId'));
        if (!$auction) {
            $this->error('Auction not found');
            return;
        }

        $user = User::first(); // Just use first user
        
        // Create a fake bid in memory (or db if needed, but lets try memory first to see if it serializes)
        // Actually, SerializesModels trait requires it to be in DB usually.
        // Let's CREATE a real bid.
        
        $bid = Bid::create([
            'auction_id' => $auction->id,
            'user_id' => $user->id,
            'amount' => $auction->current_price + 5.00,
        ]);

        $this->info("Dispatching BidPlaced for Bid ID: {$bid->id} on Auction {$auction->id}");
        
        BidPlaced::dispatch($bid);
        
        $this->info('Dispatched.');
    }
}
