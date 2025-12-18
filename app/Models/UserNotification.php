<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'auction_id',
        'data',
        'group_key',
        'unread_count',
        'last_seen_at',
    ];

    protected $casts = [
        'data' => 'array',
        'last_seen_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auction()
    {
        return $this->belongsTo(Auction::class);
    }

    public function markAsRead()
    {
        $this->update(['last_seen_at' => now(), 'unread_count' => 0]);
    }
}
