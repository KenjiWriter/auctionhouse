<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserNotificationSetting;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Create or update a grouped notification.
     */
    public function notify(User $user, string $type, ?int $auctionId = null, array $data = [])
    {
        // 1. Check settings
        $settings = $user->notificationSettings ?? UserNotificationSetting::firstOrCreate(['user_id' => $user->id]);
        
        // If completely disabled for this channel, return (assuming in-app for now)
        if (!$settings->enable_in_app) {
            return null;
        }

        // 2. Determine Group Key
        $groupKey = $auctionId ? "{$type}:{$auctionId}" : "{$type}:general";

        // 3. Update or Create logic (Atomic usually, but Eloquent is fine for this scale)
        // We want to update IF unread exists. If read (last_seen_at set) or not exists, create new.
        
        $existing = UserNotification::where('user_id', $user->id)
            ->where('group_key', $groupKey)
            ->whereNull('last_seen_at') // Only group with UNREAD
            ->latest()
            ->first();

        if ($existing) {
            $existing->update([
                'data' => array_merge($existing->data ?? [], $data), // Merge new data
                'unread_count' => $existing->unread_count + 1,
                'updated_at' => now(),
            ]);
            $notification = $existing;
        } else {
            $notification = UserNotification::create([
                'user_id' => $user->id,
                'type' => $type,
                'auction_id' => $auctionId,
                'data' => $data,
                'group_key' => $groupKey,
                'unread_count' => 1,
            ]);
        }

        // 4. Broadcast
        NotificationCreated::dispatch($notification);

        return $notification;
    }
    
    /**
     * Mark all as read for user
     */
    public function markAllAsRead(User $user)
    {
        $user->notifications()
            ->whereNull('last_seen_at')
            ->update(['last_seen_at' => now()]);
    }
}
