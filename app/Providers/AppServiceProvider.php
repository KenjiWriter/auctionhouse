<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\AuctionEnded::class,
            function (\App\Events\AuctionEnded $event) {
                // Determine actions based on auction result
                // If sold, notify winner and seller
                if ($event->auction->status === \App\Models\Auction::STATUS_ENDED && $event->auction->winner_id) {
                    $winner = $event->auction->winner;
                    $seller = $event->auction->user;

                    if ($winner) {
                        $winner->notify(new \App\Notifications\AuctionWonNotification($event->auction));
                    }
                    if ($seller) {
                        $seller->notify(new \App\Notifications\AuctionSoldNotification($event->auction, $winner));
                    }
                }
            }
        );
    }
}
