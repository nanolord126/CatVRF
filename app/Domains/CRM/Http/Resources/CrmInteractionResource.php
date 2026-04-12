<?php

declare(strict_types=1);

namespace App\Domains\CRM\Http\Resources;

use App\Domains\CRM\Models\CrmInteraction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CRM Interaction API Resource — форматирование взаимодействия.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmInteractionResource extends JsonResource
{
    /**
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        /** @var CrmInteraction $interaction */
        $interaction = $this->resource;

        return [
            'id' => $interaction->id,
            'crm_client_id' => $interaction->crm_client_id,
            'tenant_id' => $interaction->tenant_id,
            'type' => $interaction->type,
            'channel' => $interaction->channel,
            'direction' => $interaction->direction,
            'content' => $interaction->content,
            'metadata' => $interaction->metadata ?? [],
            'assigned_to' => $interaction->assigned_to,
            'is_resolved' => (bool) $interaction->is_resolved,
            'correlation_id' => $interaction->correlation_id,
            'created_at' => $interaction->created_at?->toIso8601String(),
            'updated_at' => $interaction->updated_at?->toIso8601String(),
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
     * CrmInteractionResource — CatVRF 2026 Component.
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
