<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Ports;

use App\Domains\VerticalName\DTOs\CreateVerticalItemDto;
use App\Domains\VerticalName\DTOs\SearchVerticalItemDto;
use App\Domains\VerticalName\DTOs\UpdateVerticalItemDto;
use App\Domains\VerticalName\Models\VerticalItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Port (интерфейс) репозитория VerticalItem.
 *
 * CANON 2026 — Hexagonal Architecture: Ports & Adapters.
 * Этот интерфейс определяет контракт доступа к данным.
 * Реализация — в Infrastructure слое (EloquentVerticalItemRepository).
 *
 * Все методы обязаны соблюдать tenant isolation.
 * correlation_id передаётся для traceability.
 *
 * @package App\Domains\VerticalName\Ports
 */
interface VerticalItemRepositoryPort
{
    /**
     * Найти item по ID (tenant-scoped).
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findById(int $id, int $tenantId): VerticalItem;

    /**
     * Найти item по UUID (tenant-scoped).
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findByUuid(string $uuid, int $tenantId): VerticalItem;

    /**
     * Создать новый item из DTO.
     */
    public function create(CreateVerticalItemDto $dto): VerticalItem;

    /**
     * Обновить item из DTO.
     */
    public function update(VerticalItem $item, UpdateVerticalItemDto $dto): VerticalItem;

    /**
     * Мягкое удаление item.
     */
    public function delete(VerticalItem $item, string $correlationId): bool;

    /**
     * Поиск с фильтрацией и пагинацией.
     */
    public function search(SearchVerticalItemDto $dto): LengthAwarePaginator;

    /**
     * Получить все B2B-доступные товары для tenant.
     */
    public function getB2bItems(int $tenantId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Получить все опубликованные товары (B2C).
     */
    public function getPublishedItems(int $tenantId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Обновить остаток на складе.
     */
    public function updateStock(int $itemId, int $tenantId, int $quantityDelta, string $correlationId): VerticalItem;

    /**
     * Пересчитать рейтинг на основе отзывов.
     */
    public function recalculateRating(int $itemId, int $tenantId): VerticalItem;
}
