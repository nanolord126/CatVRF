<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Application\B2C\UseCases;

use App\Domains\Beauty\Domain\Entities\Salon;
use App\Domains\Beauty\Domain\Repositories\SalonRepositoryInterface;
use App\Domains\Beauty\Domain\ValueObjects\SalonId;

/**
 * B2C: Получить детальную информацию о конкретном салоне со списком мастеров и услуг.
 *
 * Канон CatVRF 2026 — 9-слойная архитектура.
 * Application layer UseCase.
 *
 * @package App\Domains\Beauty\Application\B2C\UseCases
 */
final readonly class GetSalonDetailsUseCase
{
    public function __construct(
        private SalonRepositoryInterface $salonRepository,
    ) {
    }

    /**
     * Получить детальную информацию о салоне по UUID.
     *
     * @param string $salonUuid UUID салона
     * @return Salon Доменная сущность салона
     * @throws \DomainException если салон не найден
     */
    public function __invoke(string $salonUuid): Salon
    {
        $salonId = SalonId::fromString($salonUuid);
        $salon   = $this->salonRepository->findById($salonId);

        if ($salon === null) {
            throw new \DomainException("Salon [{$salonUuid}] not found.");
        }

        return $salon;
    }

    /**
     * Имя UseCase для audit-логирования.
     */
    public function getUseCaseName(): string
    {
        return 'beauty.b2c.get_salon_details';
    }

    /**
     * Строковое представление UseCase.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class;
    }
}
