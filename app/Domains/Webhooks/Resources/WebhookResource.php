<?php declare(strict_types=1);

namespace App\Domains\Webhooks\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\Webhooks\Models\Webhook;

final class WebhookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Webhook $this */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'url' => $this->url,
            'events' => $this->events,
            'is_active' => $this->is_active,
            'retry_count' => $this->retry_count,
            'timeout' => $this->timeout,
            'headers' => $this->headers,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
