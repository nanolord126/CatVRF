<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Services;

use App\Domains\Hotels\DTOs\PropertyTypeDto;
use App\Domains\Hotels\Models\PropertyType;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * PropertyTypeService — сервис управления типами размещения.
 *
 * Layer 3: Services — CatVRF 9-layer architecture.
 *
 * Управляет типами размещения: отели, санатории, пансионаты,
 * квартиры посуточно, апарты, хостелы и т.д.
 *
 * @package App\Domains\Hotels\Services
 * @version 2026.1
 */
final readonly class PropertyTypeService
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
    ) {}

    /**
     * Создать новый тип размещения.
     *
     * @param PropertyTypeDto $dto DTO с данными типа размещения
     * @param string $correlationId ID корреляции
     *
     * @return PropertyType Созданный тип размещения
     *
     * @throws \DomainException
     */
    public function create(PropertyTypeDto $dto, string $correlationId): PropertyType
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'property_type_create',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($dto, $correlationId) {
            $existingType = PropertyType::where('slug', $dto->slug)
                ->where('tenant_id', $dto->tenantId)
                ->first();

            if ($existingType !== null) {
                throw new \DomainException('Тип размещения с таким slug уже существует');
            }

            $propertyType = PropertyType::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $dto->tenantId,
                'slug' => $dto->slug,
                'name' => $dto->name,
                'name_ru' => $dto->nameRu,
                'description' => $dto->description,
                'icon' => $dto->icon,
                'is_active' => $dto->isActive,
                'sort_order' => $dto->sortOrder,
                'min_stars' => $dto->minStars,
                'max_stars' => $dto->maxStars,
                'features' => $dto->features,
                'metadata' => $dto->metadata,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                action: 'property_type_created',
                subjectType: PropertyType::class,
                subjectId: $propertyType->id,
                oldValues: [],
                newValues: $propertyType->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Property type created', [
                'property_type_id' => $propertyType->id,
                'slug' => $propertyType->slug,
                'name_ru' => $propertyType->name_ru,
                'correlation_id' => $correlationId,
            ]);

            return $propertyType;
        });
    }

    /**
     * Обновить тип размещения.
     *
     * @param int $propertyTypeId ID типа размещения
     * @param PropertyTypeDto $dto DTO с обновлёнными данными
     * @param string $correlationId ID корреляции
     *
     * @return PropertyType Обновлённый тип размещения
     *
     * @throws \DomainException
     */
    public function update(int $propertyTypeId, PropertyTypeDto $dto, string $correlationId): PropertyType
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'property_type_update',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($propertyTypeId, $dto, $correlationId) {
            $propertyType = PropertyType::findOrFail($propertyTypeId);

            $oldData = $propertyType->toArray();

            $propertyType->update([
                'slug' => $dto->slug,
                'name' => $dto->name,
                'name_ru' => $dto->nameRu,
                'description' => $dto->description,
                'icon' => $dto->icon,
                'is_active' => $dto->isActive,
                'sort_order' => $dto->sortOrder,
                'min_stars' => $dto->minStars,
                'max_stars' => $dto->maxStars,
                'features' => $dto->features,
                'metadata' => $dto->metadata,
            ]);

            $this->audit->log(
                action: 'property_type_updated',
                subjectType: PropertyType::class,
                subjectId: $propertyType->id,
                oldValues: $oldData,
                newValues: $propertyType->fresh()->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Property type updated', [
                'property_type_id' => $propertyType->id,
                'slug' => $propertyType->slug,
                'correlation_id' => $correlationId,
            ]);

            return $propertyType->fresh();
        });
    }

    /**
     * Удалить тип размещения.
     *
     * @param int $propertyTypeId ID типа размещения
     * @param string $correlationId ID корреляции
     *
     * @return bool Результат удаления
     *
     * @throws \DomainException
     */
    public function delete(int $propertyTypeId, string $correlationId): bool
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'property_type_delete',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($propertyTypeId, $correlationId) {
            $propertyType = PropertyType::findOrFail($propertyTypeId);

            $hotelsCount = $propertyType->hotels()->count();

            if ($hotelsCount > 0) {
                throw new \DomainException('Нельзя удалить тип размещения, к которому привязаны отели');
            }

            $oldData = $propertyType->toArray();

            $propertyType->delete();

            $this->audit->log(
                action: 'property_type_deleted',
                subjectType: PropertyType::class,
                subjectId: $propertyTypeId,
                oldValues: $oldData,
                newValues: [],
                correlationId: $correlationId,
            );

            $this->logger->info('Property type deleted', [
                'property_type_id' => $propertyTypeId,
                'slug' => $oldData['slug'] ?? '',
                'correlation_id' => $correlationId,
            ]);

            return true;
        });
    }

    /**
     * Получить все активные типы размещения.
     *
     * @param int $tenantId ID tenant
     * @param string $correlationId ID корреляции
     *
     * @return array<int, PropertyType> Список типов размещения
     */
    public function getActiveTypes(int $tenantId, string $correlationId): array
    {
        $types = PropertyType::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->toArray();

        $this->logger->info('Active property types retrieved', [
            'tenant_id' => $tenantId,
            'count' => count($types),
            'correlation_id' => $correlationId,
        ]);

        return $types;
    }

    /**
     * Получить тип размещения по slug.
     *
     * @param string $slug Slug типа размещения
     * @param int $tenantId ID tenant
     * @param string $correlationId ID корреляции
     *
     * @return PropertyType Тип размещения
     *
     * @throws \DomainException
     */
    public function getBySlug(string $slug, int $tenantId, string $correlationId): PropertyType
    {
        $propertyType = PropertyType::where('slug', $slug)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->firstOrFail();

        $this->logger->info('Property type retrieved by slug', [
            'slug' => $slug,
            'property_type_id' => $propertyType->id,
            'correlation_id' => $correlationId,
        ]);

        return $propertyType;
    }

    /**
     * Активировать тип размещения.
     *
     * @param int $propertyTypeId ID типа размещения
     * @param string $correlationId ID корреляции
     *
     * @return PropertyType Обновлённый тип размещения
     */
    public function activate(int $propertyTypeId, string $correlationId): PropertyType
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'property_type_activate',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($propertyTypeId, $correlationId) {
            $propertyType = PropertyType::findOrFail($propertyTypeId);

            $oldData = $propertyType->toArray();

            $propertyType->update(['is_active' => true]);

            $this->audit->log(
                action: 'property_type_activated',
                subjectType: PropertyType::class,
                subjectId: $propertyType->id,
                oldValues: $oldData,
                newValues: $propertyType->fresh()->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Property type activated', [
                'property_type_id' => $propertyType->id,
                'correlation_id' => $correlationId,
            ]);

            return $propertyType->fresh();
        });
    }

    /**
     * Деактивировать тип размещения.
     *
     * @param int $propertyTypeId ID типа размещения
     * @param string $correlationId ID корреляции
     *
     * @return PropertyType Обновлённый тип размещения
     */
    public function deactivate(int $propertyTypeId, string $correlationId): PropertyType
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'property_type_deactivate',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($propertyTypeId, $correlationId) {
            $propertyType = PropertyType::findOrFail($propertyTypeId);

            $oldData = $propertyType->toArray();

            $propertyType->update(['is_active' => false]);

            $this->audit->log(
                action: 'property_type_deactivated',
                subjectType: PropertyType::class,
                subjectId: $propertyType->id,
                oldValues: $oldData,
                newValues: $propertyType->fresh()->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Property type deactivated', [
                'property_type_id' => $propertyType->id,
                'correlation_id' => $correlationId,
            ]);

            return $propertyType->fresh();
        });
    }
}
