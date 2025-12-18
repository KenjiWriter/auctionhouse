<?php

namespace App\Services;

use App\Models\User;
use App\Models\Auction;
use App\Models\UserCategoryPreference;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RecommendationService
{
    /**
     * Get personalized auction recommendations for a user.
     *
     * @param User $user
     * @param int $limit
     * @return Collection
     */
    public function getForYouAuctions(User $user, int $limit = 12): Collection
    {
        // Cache key: recommendations:user:{id}
        // TTL: 10 minutes (600 seconds)
        return Cache::remember("recommendations:user:{$user->id}", 600, function () use ($user, $limit) {
            $recommendations = collect();

            // 1. Get Top Categories (Limit 3)
            $topCategories = UserCategoryPreference::where('user_id', $user->id)
                ->orderByDesc('score')
                ->orderByDesc('last_interaction_at')
                ->limit(3)
                ->pluck('category_id');

            if ($topCategories->isNotEmpty()) {
                // 2. Fetch auctions from these categories
                // We fetch up to 4 from each to ensure variety
                foreach ($topCategories as $categoryId) {
                    $auctions = Auction::where('category_id', $categoryId)
                        ->whereIn('status', [Auction::STATUS_ACTIVE, Auction::STATUS_UPCOMING])
                        ->where('user_id', '!=', $user->id) // Exclude own
                        ->whereDoesntHave('bids', function ($q) use ($user) {
                             $q->where('user_id', $user->id); // Exclude already bid on
                        })
                        ->orderBy('starts_at', 'asc') // Upcoming/Active (depends on data, but starts_at usually past for active)
                        // If starts_at is in past (Active), simple ordering might put old ones first. 
                        // Let's sort by ends_at ASC (ending soon) for Active/Upcoming mixed used commonly.
                        // Plan said: "starts_at asc (upcoming first)". Let's stick to plan but verify logic.
                        // If starts_at is crucial for "Upcoming", we want them. 
                        // Let's assume standard ordering logic useful for users.
                        ->orderBy('starts_at', 'asc') 
                        ->limit(4)
                        ->with('category', 'images', 'user')
                        ->get();

                    $recommendations = $recommendations->merge($auctions);
                }
            }
            
            // Deduplicate
            $recommendations = $recommendations->unique('id');

            // 3. Fallback / Fill with Trending
            if ($recommendations->count() < $limit) {
                $needed = $limit - $recommendations->count();
                $excludedIds = $recommendations->pluck('id')->toArray();
                
                $trending = $this->getTrendingAuctions($needed, $excludedIds, $user);
                $recommendations = $recommendations->merge($trending);
            }

            return $recommendations->take($limit);
        });
    }

    /**
     * Get trending auctions (fallback).
     */
    public function getTrendingAuctions(int $limit, array $excludedIds = [], ?User $user = null): Collection
    {
        $query = Auction::whereIn('status', [Auction::STATUS_ACTIVE, Auction::STATUS_UPCOMING])
            ->whereNotIn('id', $excludedIds)
            ->withCount('bids') // Popularity metric
            ->orderByDesc('bids_count')
            ->orderBy('ends_at', 'asc')
            ->limit($limit)
            ->with(['category', 'images', 'user']); // Explicit eager load

        if ($user) {
             $query->where('user_id', '!=', $user->id);
        }

        return $query->get();
    }
}
