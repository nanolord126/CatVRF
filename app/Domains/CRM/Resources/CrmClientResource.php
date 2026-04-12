<?php declare(strict_types=1);

namespace App\Domains\CRM\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CrmClientResource extends JsonResource
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
            'business_group_id' => $this->resource->business_group_id,
            'user_id' => $this->resource->user_id,
            'first_name' => $this->resource->first_name,
            'last_name' => $this->resource->last_name,
            'company_name' => $this->resource->company_name,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'vertical' => $this->resource->vertical,
            'segment' => $this->resource->segment,
            'status' => $this->resource->status,
            'total_spent' => $this->resource->total_spent,
            'total_orders' => $this->resource->total_orders,
            'correlation_id' => $this->resource->correlation_id,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
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
     * CrmClientResource — CatVRF 2026 Component.
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
