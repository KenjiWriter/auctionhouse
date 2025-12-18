<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Auction;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Models\WatchNotificationLog;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SendWatchlistReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-watchlist-reminders {--now= : Override current time for testing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send watchlist start/end reminder notifications';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $now = $this->option('now') ? Carbon::parse($this->option('now')) : now();
        $this->info("Running watchlist reminders at {$now}");

        $startCount = $this->processStartingReminders($now, $notificationService);
        $endCount = $this->processEndingReminders($now, $notificationService);

        $this->info("Sent {$startCount} starting reminders and {$endCount} ending reminders.");
    }

    protected function processStartingReminders(Carbon $now, NotificationService $notificationService): int
    {
        $count = 0;
        $offsets = UserNotificationSetting::DEFAULT_START_OFFSETS;

        foreach ($offsets as $offsetMinutes) {
            $targetStart = $now->copy()->addMinutes($offsetMinutes);
            $windowStart = $targetStart->copy()->subSeconds(30);
            $windowEnd = $targetStart->copy()->addSeconds(30);

            // Find auctions starting in this window
            $auctions = Auction::where('status', Auction::STATUS_UPCOMING)
                ->whereBetween('starts_at', [$windowStart, $windowEnd])
                ->pluck('id');

            if ($auctions->isEmpty()) continue;

            // Find all watchers for these auctions
            $watchers = DB::table('auction_user')
                ->whereIn('auction_id', $auctions)
                ->get();

            foreach ($watchers as $watcher) {
                // Check if we already sent this notification
                $exists = WatchNotificationLog::where('user_id', $watcher->user_id)
                    ->where('auction_id', $watcher->auction_id)
                    ->where('type', 'starting')
                    ->where('offset_minutes', $offsetMinutes)
                    ->exists();

                if ($exists) continue;

                // Check user settings
                $settings = UserNotificationSetting::where('user_id', $watcher->user_id)->first();
                $userOffsets = $settings?->watch_start_offsets ?? UserNotificationSetting::DEFAULT_START_OFFSETS;
                
                if (!in_array($offsetMinutes, $userOffsets)) continue;

                // Send notification
                $auction = Auction::find($watcher->auction_id);
                $user = User::find($watcher->user_id);

                if ($auction && $user) {
                    $notificationService->notify(
                        $user,
                        'watch_auction_starting',
                        $auction->id,
                        [
                            'auction_title' => $auction->title,
                            'starts_at' => $auction->starts_at->toISOString(),
                            'offset_minutes' => $offsetMinutes,
                        ]
                    );

                    // Log it
                    WatchNotificationLog::create([
                        'user_id' => $watcher->user_id,
                        'auction_id' => $watcher->auction_id,
                        'type' => 'starting',
                        'offset_minutes' => $offsetMinutes,
                    ]);

                    $count++;
                }
            }
        }

        return $count;
    }

    protected function processEndingReminders(Carbon $now, NotificationService $notificationService): int
    {
        $count = 0;
        $offsets = UserNotificationSetting::DEFAULT_END_OFFSETS;

        foreach ($offsets as $offsetMinutes) {
            $targetEnd = $now->copy()->addMinutes($offsetMinutes);
            $windowStart = $targetEnd->copy()->subSeconds(30);
            $windowEnd = $targetEnd->copy()->addSeconds(30);

            // Find auctions ending in this window
            $auctions = Auction::where('status', Auction::STATUS_ACTIVE)
                ->whereBetween('ends_at', [$windowStart, $windowEnd])
                ->pluck('id');

            if ($auctions->isEmpty()) continue;

            // Find all watchers
            $watchers = DB::table('auction_user')
                ->whereIn('auction_id', $auctions)
                ->get();

            foreach ($watchers as $watcher) {
                // Check dedupe
                $exists = WatchNotificationLog::where('user_id', $watcher->user_id)
                    ->where('auction_id', $watcher->auction_id)
                    ->where('type', 'ending')
                    ->where('offset_minutes', $offsetMinutes)
                    ->exists();

                if ($exists) continue;

                // Check user settings
                $settings = UserNotificationSetting::where('user_id', $watcher->user_id)->first();
                $userOffsets = $settings?->watch_end_offsets ?? UserNotificationSetting::DEFAULT_END_OFFSETS;
                
                if (!in_array($offsetMinutes, $userOffsets)) continue;

                // Send
                $auction = Auction::find($watcher->auction_id);
                $user = User::find($watcher->user_id);

                if ($auction && $user) {
                    $notificationService->notify(
                        $user,
                        'watch_auction_ending',
                        $auction->id,
                        [
                            'auction_title' => $auction->title,
                            'ends_at' => $auction->ends_at->toISOString(),
                            'offset_minutes' => $offsetMinutes,
                            'current_price' => $auction->current_price ?? $auction->starting_price,
                        ]
                    );

                    WatchNotificationLog::create([
                        'user_id' => $watcher->user_id,
                        'auction_id' => $watcher->auction_id,
                        'type' => 'ending',
                        'offset_minutes' => $offsetMinutes,
                    ]);

                    $count++;
                }
            }
        }

        return $count;
    }
}
