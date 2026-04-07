<?php declare(strict_types=1);

namespace App\Domains\Freelance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class FreelanceOrderResource
 *
 * Part of the Freelance vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * API Resource for response transformation.
 * Formats model data for API responses.
 * Always includes correlation_id in meta.
 *
 * @package App\Domains\Freelance\Http\Resources
 */
final class FreelanceOrderResource extends JsonResource
{
    /**
     * Трансформация ресурса в массив.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'client_id' => $this->client_id,
            'freelancer_id' => $this->freelancer_id,
            'title' => $this->title,
            'requirements' => $this->requirements,
            'budget_kopecks' => $this->budget_kopecks,
            'deadline_at' => $this->deadline_at,
            'correlation_id' => $this->correlation_id,
            'tenant_id' => $this->tenant_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Дополнительные мета-данные ответа.
     *
     * @return array<string, mixed>
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
