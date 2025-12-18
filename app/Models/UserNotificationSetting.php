<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotificationSetting extends Model
{
    use HasFactory;

    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'watch_start_offsets', // json
        'watch_end_offsets',   // json
        'enable_in_app',
        'enable_email',
        'quiet_hours_start',
        'quiet_hours_end',
    ];

    protected $casts = [
        'watch_start_offsets' => 'array',
        'watch_end_offsets' => 'array',
        'enable_in_app' => 'boolean',
        'enable_email' => 'boolean',
    ];

    // Defaults if record doesn't exist (handled in service usually, but good to know)
    const DEFAULT_START_OFFSETS = [60, 10]; // 1h, 10m
    const DEFAULT_END_OFFSETS = [30, 5];   // 30m, 5m

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
