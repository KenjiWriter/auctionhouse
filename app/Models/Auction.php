<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auction extends Model
{
    // Status Constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_UPCOMING = 'upcoming';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ENDED = 'ended';
    public const STATUS_ENDED_WITHOUT_SALE = 'ended_without_sale';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'starting_price',
        'current_price',
        'buy_now_price',
        'starts_at',
        'ends_at',
        'status',
        'winner_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'starting_price' => 'decimal:2',
        'current_price' => 'decimal:2',
        'buy_now_price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(AuctionImage::class)->orderBy('order');
    }

    public function bids()
    {
        return $this->hasMany(Bid::class)->orderByDesc('amount');
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', self::STATUS_UPCOMING);
    }

    public function scopeEnded($query)
    {
        return $query->whereIn('status', [self::STATUS_ENDED, self::STATUS_ENDED_WITHOUT_SALE]);
    }

    public function watchers()
    {
        return $this->belongsToMany(User::class, 'auction_user')->withTimestamps();
    }

    public function autoBids()
    {
        return $this->hasMany(AutoBid::class);
    }

    public function scopeWithIsWatched($query)
    {
        $user = request()->user();
        if ($user) {
            return $query->withExists(['watchers as is_watched' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            }]);
        }
        return $query;
    }
}
