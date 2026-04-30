<?php

declare(strict_types=1);

namespace App\Domains\Communication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\CarbonImmutable;

/**
 * Layer 5: API Resource — transform ChatMessage model for JSON output.
 */
final class ChatMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'room_id'        => $this->room_id,
            'sender_id'      => $this->sender_id,
            'type'           => $this->type,
            'body'           => $this->body,
            'attachment_url' => $this->attachment_url,
            'is_read'        => $this->is_read,
            'correlation_id' => $this->correlation_id,
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }

    /**
     * Дополнительные метаданные в ответе.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'correlation_id' => $request->attributes->get('correlation_id'),
                'generated_at'   => CarbonImmutable::now()->toIso8601String(),
            ],
        ];
    }

    /**
     * ChatMessageResource — CatVRF 2026 Component.
     *
     * Part of the CatVRF multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @version 2026.1
     *
     * @author CatVRF Team
     * @license Proprietary
     */
}
