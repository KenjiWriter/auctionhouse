<?php

namespace App\Services;

use App\Models\User;
use App\Models\Auction;
use App\Models\UserActivityEvent;
use App\Models\UserCategoryPreference;
use Illuminate\Support\Facades\DB;

class UserActivityService
{
    private const SCORE_MAP = [
        'auction_view' => 1,
        'category_view' => 2,
        'watch' => 3,
        'bid' => 5,
    ];

    /**
     * Log a user activity event and update category preferences.
     */
    public function log(User $user, string $type, ?int $auctionId = null, ?int $categoryId = null, array $metadata = []): void
    {
        // Resolve category if possible
        if (!$categoryId && $auctionId) {
            $auction = Auction::find($auctionId);
            $categoryId = $auction?->category_id;
        }

        // 1. Create Event Log
        UserActivityEvent::create([
            'user_id' => $user->id,
            'type' => $type,
            'auction_id' => $auctionId,
            'category_id' => $categoryId,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);

        // 2. Update Preference Score (if associated with a category)
        if ($categoryId && isset(self::SCORE_MAP[$type])) {
            $this->updatePreference($user, $categoryId, self::SCORE_MAP[$type]);
        }
    }

    /**
     * Update the preference score for a user and category.
     */
    protected function updatePreference(User $user, int $categoryId, int $scoreDelta): void
    {
        // Use upsert or firstOrCreate to ensure record exists
        // We'll increment the score.
        
        // Using Eloquent's firstOrNew/firstOrCreate can be race-condition prone without lock, 
        // but for this scale, atomic increment or raw query is fine. 
        // Let's us updateOrInsert or just find and save.
        
        $preference = UserCategoryPreference::firstOrNew([
            'user_id' => $user->id,
            'category_id' => $categoryId,
        ]);

        $preference->score += $scoreDelta;
        $preference->last_interaction_at = now();
        $preference->save();
        
        // Optional: Invalidate cache for this user recommendations here?
        // RecommendationService will handle caching, but we might want to tag it or just let TTL expire.
        // User requirements say: "Invalidate cache when: user views a new auction, user bids, user watches"
        // So yes, we should invalidate here. 
        // I will use Cache facade. Key: recommendations:user:{id}
        // Since I haven't implemented RecommendationService yet, I'll assume the key format from plan.
        \Illuminate\Support\Facades\Cache::forget("recommendations:user:{$user->id}");
    }
}
