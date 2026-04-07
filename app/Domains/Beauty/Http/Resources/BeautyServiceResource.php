<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource для BeautyService.
 * Канон CatVRF 2026: declare(strict_types=1), final class, correlation_id обязателен.
 */
final class BeautyServiceResource extends JsonResource
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
            'salon_id' => $this->salon_id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'duration_minutes' => $this->duration_minutes,
            'category' => $this->category,
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

    /**
     * Дополнительные заголовки ответа для трассировки.
     *
     * @return array<string, string>
     */
    public function withResponse(Request $request, \Illuminate\Http\JsonResponse $response): void
    {
        $response->header(
            'X-Correlation-ID',
            $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid()),
        );
    }
}
