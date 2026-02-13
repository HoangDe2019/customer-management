<?php

use Illuminate\Support\Facades\Broadcast;

// ✅ Make sure this exists
Broadcast::channel('user.{userId}', function ($user, $userId) {
    \Log::info('Channel auth attempt', [
        'user_id' => $user->id,
        'requested_userId' => $userId,
        'matches' => (int) $user->id === (int) $userId,
    ]);

    return (int) $user->id === (int) $userId;
});
