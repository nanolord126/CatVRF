<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Ports;

use App\Domains\VerticalName\DTOs\CreateVerticalItemDto;
use App\Domains\VerticalName\DTOs\SearchVerticalItemDto;
use App\Domains\VerticalName\DTOs\UpdateVerticalItemDto;
use App\Domains\VerticalName\Models\VerticalItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Port (интерфейс) сервиса VerticalItem.
 *
 * CANON 2026 — Hexagonal Architecture: Ports & Adapters.
 * Этот интерфейс определяет контракт бизнес-логики вертикали.
 * Реализация — в Services\VerticalItemService.
 *
 * Все методы обязаны:
 * - FraudControlService::check() перед мутациями
 * - DB::transaction() для мутаций
 * - AuditService::record() после мутаций
 * - correlation_id в каждом вызове
 *
 * @package App\Domains\VerticalName\Ports
 */
interface VerticalItemServicePort
{
    /**
     * Создать новый товар.
     *
     * Включает: fraud check, DB transaction, audit log, event dispatch.
     */
    public function createItem(CreateVerticalItemDto $dto): VerticalItem;

    /**
     * Обновить товар.
     *
     * Включает: fraud check, DB transaction, audit log, event dispatch.
     */
    public function updateItem(UpdateVerticalItemDto $dto): VerticalItem;

    /**
     * Мягкое удаление товара.
     *
     * Включает: fraud check, audit log.
     */
    public function deleteItem(int $itemId, int $tenantId, string $correlationId): bool;

    /**
     * Получить товар по ID.
     */
    public function getById(int $itemId, int $tenantId): VerticalItem;

    /**
     * Поиск товаров с фильтрацией.
     */
    public function search(SearchVerticalItemDto $dto): LengthAwarePaginator;

    /**
     * Получить B2B-каталог (только для Tenant Panel / B2B API).
     */
    public function getB2bCatalog(int $tenantId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Получить B2C-каталог (публичная витрина).
     */
    public function getPublicCatalog(int $tenantId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Зарезервировать товар (для корзины, 20 мин).
     */
    public function reserveStock(int $itemId, int $tenantId, int $quantity, string $correlationId): bool;

    /**
     * Снять резерв.
     */
    public function releaseStock(int $itemId, int $tenantId, int $quantity, string $correlationId): bool;
}
