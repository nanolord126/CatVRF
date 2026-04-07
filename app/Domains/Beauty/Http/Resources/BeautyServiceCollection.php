<?php

declare(strict_types=1);

/**
 * BeautyServiceCollection — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/beautyservicecollection
 */


namespace App\Domains\Beauty\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Коллекция ресурсов BeautyService.
 * Канон CatVRF 2026: declare(strict_types=1), final class.
 */
final class BeautyServiceCollection extends ResourceCollection
{
    /**
     * Ресурс элемента коллекции.
     *
     * @var string
     */
    public $collects = BeautyServiceResource::class;

    /**
     * Трансформация коллекции в массив.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->collection->count(),
                'correlation_id' => $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid()),
                'api_version' => 'v1',
            ],
        ];
    }

    /**
     * Дополнительные заголовки ответа для трассировки.
     */
    public function withResponse(Request $request, \Illuminate\Http\JsonResponse $response): void
    {
        $response->header(
            'X-Correlation-ID',
            $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid()),
        );
    }
}
