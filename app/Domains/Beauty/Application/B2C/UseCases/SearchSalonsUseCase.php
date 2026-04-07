<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Application\B2C\UseCases;


use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

use App\Domains\Beauty\Application\B2C\DTOs\SalonSearchDTO;
use App\Domains\Beauty\Domain\Repositories\SalonRepositoryInterface;
use App\Shared\Domain\ValueObjects\TenantId;
use Illuminate\Support\Collection;
/**
 * B2C: Поиск салонов с фильтрацией по категории, гео, цене.
 */
final readonly class SearchSalonsUseCase
{
    public function __construct(
        private SalonRepositoryInterface $salonRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SalonSearchDTO $dto): Collection
    {
        $salons = $this->salonRepository->findByTenantId(new TenantId($dto->tenantId));

        if ($dto->category !== null) {
            $salons = $salons->filter(
                fn ($salon) => $salon->toArray()['category'] === $dto->category
            );
        }

        if ($dto->maxPriceRub !== null) {
            $salons = $salons->filter(function ($salon) use ($dto) {
                $minPrice = collect($salon->services ?? [])
                    ->min(fn ($s) => $s->price->getAmountInCents());
                return $minPrice === null || ($minPrice / 100) <= $dto->maxPriceRub;
            });
        }

        $this->logger->info('B2C: Salon search', [
            'tenant_id' => $dto->tenantId,
            'category'  => $dto->category,
            'lat'       => $dto->lat,
            'lon'       => $dto->lon,
            'results'   => $salons->count(),
                'correlation_id' => $dto->correlationId ?? Str::uuid()->toString(),
            ]);

        return $salons
            ->skip((($dto->page ?? 1) - 1) * ($dto->perPage ?? 20))
            ->take($dto->perPage ?? 20)
            ->values();
    }

    /**
     * Имя UseCase для audit-логирования.
     */
    public function getUseCaseName(): string
    {
        return 'beauty.b2c.search_salons';
    }
}
