<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversations.{conversation}', function ($user, \App\Models\Conversation $conversation) {
    return $user->id === $conversation->buyer_id || $user->id === $conversation->seller_id;
});
