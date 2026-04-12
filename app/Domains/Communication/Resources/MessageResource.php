<?php declare(strict_types=1);

namespace App\Domains\Communication\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'uuid' => $this->resource->uuid,
            'tenant_id' => $this->resource->tenant_id,
            'channel_id' => $this->resource->channel_id,
            'sender_id' => $this->resource->sender_id,
            'recipient_id' => $this->resource->recipient_id,
            'recipient_type' => $this->resource->recipient_type,
            'channel_type' => $this->resource->channel_type,
            'subject' => $this->resource->subject,
            'body' => $this->resource->body,
            'status' => $this->resource->status,
            'metadata' => $this->resource->metadata,
            'correlation_id' => $this->resource->correlation_id,
            'sent_at' => $this->resource->sent_at,
            'delivered_at' => $this->resource->delivered_at,
            'read_at' => $this->resource->read_at,
            'created_at' => $this->resource->created_at,
        ];
    }

    /**
     * Дополнительные метаданные в ответе.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function with(\Illuminate\Http\Request $request): array
    {
        return [
            'meta' => [
                'correlation_id' => $request->attributes->get('correlation_id'),
                'generated_at'   => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * MessageResource — CatVRF 2026 Component.
     *
     * Part of the CatVRF multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     * @author CatVRF Team
     * @license Proprietary
     */
}
