<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Category;
use App\Models\Auction;
use App\Models\Bid;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class GenerateAuctions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:generate-auctions 
                            {count=50 : Number of auctions to generate}
                            {--images=1 : Number of images per auction}
                            {--starts=spread : Start time mode (soon, spread, active)}
                            {--duration=120 : Duration in minutes}
                            {--min-price=10}
                            {--max-price=500}
                            {--buy-now-rate=0.4 : Probability of having buy now price}
                            {--bids=false : Generate random bids for active auctions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate demo auctions with images and bids';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int)$this->argument('count');
        $imagesCount = (int)$this->option('images');
        $mode = $this->option('starts');
        $duration = (int)$this->option('duration');
        
        $this->info("Generating {$count} auctions (Mode: {$mode})...");

        // 1. Fetch Resources
        $sellers = User::where('email', 'like', 'demo%@demo.com')->get();
        if ($sellers->isEmpty()) {
            $this->error("No demo users found. Run 'php artisan db:seed --class=UsersSeeder' first.");
            return 1;
        }
        
        $categories = Category::whereNotNull('parent_id')->get(); // Leaf categories preferred
        if ($categories->isEmpty()) {
             $categories = Category::all();
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 0; $i < $count; $i++) {
            $seller = $sellers->random();
            $category = $categories->random();

            // 2. Determine Times
            $now = now();
            if ($mode === 'soon') {
                $startsAt = $now->clone()->addMinutes(rand(5, 360));
            } elseif ($mode === 'active') {
                $startsAt = $now->clone()->subMinutes(rand(1, $duration - 10)); // Started recently
            } else { // spread
                $startsAt = $now->clone()->addMinutes(rand(-360, 1440)); // -6h to +24h
            }
            
            $endsAt = $startsAt->clone()->addMinutes($duration);
            
            // Status
            $status = Auction::STATUS_UPCOMING;
            if ($startsAt <= $now && $endsAt > $now) {
                $status = Auction::STATUS_ACTIVE;
            } elseif ($endsAt <= $now) {
                $status = Auction::STATUS_ENDED;
            }

            // 3. Prices
            $startPrice = rand((int)$this->option('min-price'), (int)$this->option('max-price'));
            $buyNowPrice = null;
            if (rand(0, 100) / 100 <= (float)$this->option('buy-now-rate')) {
                $buyNowPrice = $startPrice + rand(20, 200);
            }

            // 4. Create Auction
            $auction = Auction::create([
                'user_id' => $seller->id,
                'category_id' => $category->id,
                'title' => ucwords(fake()->words(3, true)) . ' ' . Str::random(3), // Random suffix to avoid dupe titles
                'description' => fake()->paragraph(),
                'starting_price' => $startPrice,
                'buy_now_price' => $buyNowPrice,
                'current_price' => $status === Auction::STATUS_ACTIVE ? $startPrice : null, // Will be updated by bids
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $status,
            ]);

            // 5. Images
            for ($img = 0; $img < $imagesCount; $img++) {
                $this->attachImage($auction, $img);
            }

            // 6. Optional Bids (Only for Active)
            if ($this->option('bids') !== 'false' && $status === Auction::STATUS_ACTIVE) {
                 $this->generateBids($auction, $sellers);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done! Generated {$count} auctions.");
    }

    private function attachImage(Auction $auction, int $index)
    {
        $seed = $auction->id . '_' . $index;
        $url = "https://picsum.photos/seed/{$seed}/800/600";
        
        try {
            $contents = Http::timeout(5)->get($url)->body();
            $filename = "auctions/{$auction->id}/image_{$index}.jpg";
            
            Storage::disk('public')->put($filename, $contents);
            
            $auction->images()->create([
                'path' => $filename,
                'order' => $index,
            ]);
        } catch (\Exception $e) {
            // calculated risk: ignore image fail for demo speed
            // or maybe copy a local placeholder if implemented
        }
    }

    private function generateBids(Auction $auction, $sellers)
    {
        // Simple bid gen
        $bidCount = rand(1, 8);
        $currentPrice = $auction->starting_price;
        
        // Potential bidders (exclude seller)
        $bidders = $sellers->where('id', '!=', $auction->user_id);
        if ($bidders->isEmpty()) return;

        for ($b = 0; $b < $bidCount; $b++) {
            $bidder = $bidders->random();
            $amount = $currentPrice + rand(5, 20); // increment
            
            // Check buy now
            if ($auction->buy_now_price && $amount >= $auction->buy_now_price) {
                break; // Don't bid over buy now in this simple logic
            }

            $auction->bids()->create([
                'user_id' => $bidder->id,
                'amount' => $amount,
                'created_at' => $auction->starts_at->clone()->addMinutes(rand(1, 10 + $b)),
            ]);

            $currentPrice = $amount;
        }

        $auction->update(['current_price' => $currentPrice]);
    }
}
