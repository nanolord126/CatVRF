<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domains\Beauty\Domain\Entities\Service;
use App\Domains\Beauty\Domain\Enums\ServiceCategory;
use App\Domains\Beauty\Domain\ValueObjects\Duration;
use App\Domains\Beauty\Domain\ValueObjects\Price;
use App\Domains\Beauty\Domain\ValueObjects\ServiceId;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyService as EloquentService;

/**
 * Mapper для Service: конвертация Eloquent ↔ Domain.
 * Infrastructure layer — CatVRF 2026
 *
 * @package App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Mappers
 */
final class ServiceMapper
{
    public function toDomain(EloquentService $eloquentService): Service
    {
        return new Service(
            id: ServiceId::fromString($eloquentService->uuid),
            name: $eloquentService->name,
            category: ServiceCategory::from($eloquentService->category),
            price: Price::fromCents($eloquentService->price_cents),
            duration: Duration::fromMinutes($eloquentService->duration_minutes),
            description: $eloquentService->description,
            isActive: $eloquentService->is_active,
            createdAt: new \DateTimeImmutable($eloquentService->created_at),
            updatedAt: new \DateTimeImmutable($eloquentService->updated_at),
        );
    }

    public function toEloquent(Service $service): EloquentService
    {
        $eloquentService = EloquentService::firstOrNew(['uuid' => $service->id->getValue()]);
        $eloquentService->uuid = $service->id->getValue();
        $eloquentService->name = $service->name;
        $eloquentService->category = $service->category->value;
        $eloquentService->price_cents = $service->price->getAmountInCents();
        $eloquentService->duration_minutes = $service->duration->getMinutes();
        $eloquentService->description = $service->description;
        $eloquentService->is_active = $service->isActive;

        return $eloquentService;
    }

    /**
     * Конвертация доменной сущности в массив для массового создания.
     *
     * @return array<string, mixed>
     */
    public function toEloquentArray(Service $service): array
    {
        $eloquent = $this->toEloquent($service);

        return [
            'uuid'             => $eloquent->uuid,
            'name'             => $eloquent->name,
            'category'         => $eloquent->category,
            'price_cents'      => $eloquent->price_cents,
            'duration_minutes' => $eloquent->duration_minutes,
            'description'      => $eloquent->description,
            'is_active'        => $eloquent->is_active,
        ];
    }
}
