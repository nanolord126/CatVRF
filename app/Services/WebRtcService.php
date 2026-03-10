<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\{Carbon, Str};

class WebRtcService
{
    /**
     * Generate a unique room name for WebRTC (P2P or SFU).
     * Used for Education (tutors), Support (chat), and Appointments (doctors).
     */
    public function generateRoom(string $vertical, string $id): array
    {
        $roomId = Str::slug($vertical) . '-' . $id . '-' . Str::random(8);
        return [
            'room_id' => $roomId,
            'token' => hash_hmac('sha256', $roomId, App\Services\Infrastructure\DopplerService::get('APP_KEY')),
            'provider' => 'janus_sfu_2026',
            'expires_at' => Carbon::now()->addHours(2),
        ];
    }

    public function startCall(User $initiator, array $participants): string
    {
        // Broadcast 'webrtc.incoming' event to specific users
        return "call_initiated_successfully";
    }
}
