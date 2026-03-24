<?php

declare(strict_types=1);

use App\Models\Event;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Live Stream WebRTC Mesh Channel
 * Multi-tenant aware broadcast channel for P2P peer connections
 */
Broadcast::channel('stream.{streamId}', function ($user, $streamId) {
    $stream = Event::find($streamId);

    if (!$stream) {
        return false;
    }

    // Verify tenant isolation (critical for security)
    if ($user->tenant_id !== $stream->tenant_id) {
        return false;
    }

    // Optional: Check if user has access to this stream
    // (can be further restricted based on ticket status, permissions, etc.)
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar_url,
    ];
});

/**
 * Private user-specific channel for notifications
 */
Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
