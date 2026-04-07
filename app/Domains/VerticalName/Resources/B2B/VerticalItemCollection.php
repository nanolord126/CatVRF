<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Resources\B2B;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * API Resource Collection: коллекция VerticalItem для B2B.
 *
 * CANON 2026 — Layer 8: Resources.
 * Пагинированная коллекция товаров для B2B API.
 * Включает мета-данные: пагинация, correlation_id, tenant_id.
 *
 * @package App\Domains\VerticalName\Resources\B2B
 */
final class VerticalItemCollection extends ResourceCollection
{
    /**
     * Ресурс для каждого элемента коллекции.
     *
     * @var string
     */
    public $collects = VerticalItemResource::class;

    /**
     * Преобразование коллекции в массив.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
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
                'correlation_id' => $request->header('X-Correlation-ID', ''),
                'tenant_id' => (int) (function_exists('tenant') && tenant() !== null ? tenant()->id : 0),
                'is_b2b' => $request->has('inn') && $request->has('business_card_id'),
                'total_items' => $this->collection->count(),
            ],
        ];
    }

    /**
     * Кастомизация пагинации.
     *
     * @return array<string, mixed>
     */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [
            'pagination' => [
                'total' => $paginated['total'] ?? 0,
                'per_page' => $paginated['per_page'] ?? 20,
                'current_page' => $paginated['current_page'] ?? 1,
                'last_page' => $paginated['last_page'] ?? 1,
                'from' => $paginated['from'] ?? 0,
                'to' => $paginated['to'] ?? 0,
            ],
        ];
    }
}
