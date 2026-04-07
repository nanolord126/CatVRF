<?php declare(strict_types=1);

/**
 * InsurancePolicyResource — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/insurancepolicyresource
 */


namespace App\Domains\Insurance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class InsurancePolicyResource
 *
 * Part of the Insurance vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * API Resource for response transformation.
 * Formats model data for API responses.
 * Always includes correlation_id in meta.
 *
 * @package App\Domains\Insurance\Http\Resources
 */
final class InsurancePolicyResource extends JsonResource
{
    /**
     * Handle toArray operation.
     *
     * @throws \DomainException
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'type' => $this->type,
            'coverage_amount' => $this->coverage_amount,
            'premium' => $this->premium,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'correlation_id' => $this->correlation_id,
            'tenant_id' => $this->tenant_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Handle with operation.
     *
     * @throws \DomainException
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'correlation_id' => $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid()),
                'api_version' => 'v1',
            ],
        ];
    }
}
