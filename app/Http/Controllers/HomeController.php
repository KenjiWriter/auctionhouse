<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Auction;
use App\Services\RecommendationService;

class HomeController extends Controller
{
    public function index(Request $request, RecommendationService $recommender)
    {
        $user = $request->user();
        $recommendations = [];

        if ($user) {
            $recommendations = $recommender->getForYouAuctions($user);
        } else {
            // Unauthenticated: we can show trending or nothing.
            // Plan: "hide section OR show generic trending".
            // Let's pass generic trending as recommendations so the UI is consistent
            $recommendations = $recommender->getTrendingAuctions(8);
        }

        // Existing "Starting Soon" / "Latest" logic
        // Original logic was active -> latest -> take 8
        $auctions = Auction::with('images')
            ->where('status', Auction::STATUS_ACTIVE)
            ->latest()
            ->take(8)
            ->get()
            ->map(function ($auction) {
                return [
                    'id' => $auction->id,
                    'title' => $auction->title,
                    'image' => $auction->images->first()?->path ? '/storage/' . $auction->images->first()->path : null,
                    'current_bid' => $auction->current_price ?? $auction->starting_price,
                    'ends_at' => $auction->ends_at,
                    // Add User/Category if card component needs it, original map didn't include it but card usually needs some details.
                    // The existing Home.vue just used these fields.
                ];
            });

        return Inertia::render('Home', [
            'auctions' => $auctions,
            'recommendations' => $recommendations, // This will be a Collection of Auction models (eager loaded), unlike 'auctions' which is mapped array. 
                                                   // Frontend needs to handle both or we should standardize.
                                                   // RecommendationService returns Auction models. 
                                                   // I should map recommendations same as auctions OR check how Home.vue expects data.
                                                   // Existing Home.vue likely uses explicit props for the 'auctions'. 
                                                   // I will verify Home.vue shortly. For now, passing models is usually better if Vue handles it.
        ]);
    }
}
