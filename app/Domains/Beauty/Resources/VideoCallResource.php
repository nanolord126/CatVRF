<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VideoCallResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'call_id' => $this['call_id'],
            'room_name' => $this['room_name'],
            'token' => $this['token'],
            'master_id' => $this['master_id'],
            'master_name' => $this['master_name'],
            'duration_seconds' => $this['duration_seconds'],
            'scheduled_for' => $this['scheduled_for'],
            'expires_at' => $this['expires_at'],
        ];
    }
}
