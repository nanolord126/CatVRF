<?php declare(strict_types=1);

namespace App\Domains\Auto\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VideoInspectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => $this->resource['success'] ?? false,
            'webrtc_room_id' => $this->resource['webrtc_room_id'] ?? null,
            'webrtc_token' => $this->resource['webrtc_token'] ?? null,
            'video_call_expires_at' => $this->resource['video_call_expires_at'] ?? null,
            'signaling_server' => $this->resource['signaling_server'] ?? null,
            'turn_servers' => $this->resource['turn_servers'] ?? [],
            'correlation_id' => $this->resource['correlation_id'] ?? null,
        ];
    }
}
