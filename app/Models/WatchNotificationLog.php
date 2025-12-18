<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WatchNotificationLog extends Model
{
    use HasFactory;

    public $timestamps = false; // Only created_at manually handled or via migration useCurrent

    protected $fillable = [
        'user_id',
        'auction_id',
        'type',
        'offset_minutes',
        'created_at',
    ];
}
