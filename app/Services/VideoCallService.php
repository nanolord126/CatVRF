<?php

namespace App\Services;

use App\Models\VideoCall;
use Illuminate\Support\Str;

class VideoCallService
{
    public function createRoom($caller, $receiver = null): VideoCall
    {
        return VideoCall::create([
            'room_id' => 'room_' . Str::random(12),
            'caller_id' => $caller->id,
            'receiver_id' => $receiver?->id,
            'status' => 'initiated',
            'correlation_id' => Str::uuid()
        ]);
    }

    public function getTurnConfig(): array
    {
        return [
            'iceServers' => [
                ['urls' => 'stun:stun.l.google.com:19302'],
                [
                    'urls' => config('services.webrtc.turn_url'),
                    'username' => config('services.webrtc.turn_user'),
                    'credential' => config('services.webrtc.turn_secret'),
                ],
            ]
        ];
    }
}
