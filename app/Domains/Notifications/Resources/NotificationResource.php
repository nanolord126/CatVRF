<?php declare(strict_types=1);

namespace App\Domains\Notifications\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\Notifications\Models\Notification;

final class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Notification $this */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'channel' => $this->channel,
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
            'read_at' => $this->read_at?->format('Y-m-d H:i:s'),
            'delivered_at' => $this->delivered_at?->format('Y-m-d H:i:s'),
            'failed_at' => $this->failed_at?->format('Y-m-d H:i:s'),
            'error_message' => $this->error_message,
            'is_read' => $this->isRead(),
            'is_delivered' => $this->isDelivered(),
            'is_failed' => $this->isFailed(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
