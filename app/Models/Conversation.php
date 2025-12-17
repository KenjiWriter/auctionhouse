<?php

namespace App\Models;

use App\Models\Auction;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['auction_id', 'buyer_id', 'seller_id'];

    public function auction()
    {
        return $this->belongsTo(Auction::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
    
    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }
}
