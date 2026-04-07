<?php

declare(strict_types=1);

namespace App\Domains\Staff\Application\UseCases\B2C;

use App\Domains\Staff\Application\DTO\B2C\StaffPublicProfileDTO;
use App\Domains\Staff\Domain\Repositories\StaffMemberRepositoryInterface;
use App\Domains\Staff\Domain\ValueObjects\StaffId;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

/**
 * GetStaffPublicProfileUseCase — B2C Use Case получения публичного профиля сотрудника.
 *
 * Кэширует профиль в Redis на 3600 секунд.
 * Запрещены: статические фасады, null-возврат, незавершённые комментарии.
 * Если сотрудник не найден — бросает \DomainException.
 */
final class GetStaffPublicProfileUseCase
{
    private const CACHE_TTL_SECONDS = 3600;
    private const CACHE_PREFIX      = 'staff:public_profile:';

    public function __construct(
        private readonly StaffMemberRepositoryInterface $staffMemberRepository,
        private readonly CacheRepository $cache,
        private readonly LoggerInterface $auditLogger, private readonly LoggerInterface $logger) {

    }

    /**
     * Возвращает публичный профиль сотрудника из кэша или репозитория.
     *
     * @throws \DomainException Если сотрудник не найден.
     */
    public function execute(StaffId $staffId): StaffPublicProfileDTO
    {
        $cacheKey = self::CACHE_PREFIX . $staffId->toString();

        /** @var StaffPublicProfileDTO|null $cached */
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $staffMember = $this->staffMemberRepository->find($staffId);

        if ($staffMember === null) {
            $this->auditLogger->warning('Public profile not found for staff member.', [
                'staff_id' => $staffId->toString(),
            ]);

            throw new \DomainException(
                sprintf('Сотрудник %s не найден.', $staffId->toString())
            );
        }

        $dto = new StaffPublicProfileDTO(
            fullName:  $staffMember->getFullName(),
            vertical:  $staffMember->getVertical(),
            rating:    $this->resolveRating($staffMember),
            reviews:   $this->resolveReviews($staffMember),
            avatarUrl: $this->resolveAvatarUrl($staffMember),
        );

        $this->cache->put($cacheKey, $dto, self::CACHE_TTL_SECONDS);

        $this->auditLogger->info('Public profile accessed for staff member.', [
            'staff_id'  => $staffId->toString(),
            'vertical'  => $staffMember->getVertical()->value,
        ]);

        return $dto;
    }

    /**
     * Резольвер рейтинга сотрудника (в будущем заменяется вызовом в ReviewService).
     */
    private function resolveRating(mixed $staffMember): float
    {
        return (float) ($staffMember->getRating() ?? 0.0);
    }

    /**
     * Резольвер отзывов сотрудника (в будущем заменяется вызовом в ReviewService).
     */
    private function resolveReviews(mixed $staffMember): Collection
    {
        return $staffMember->getReviews() ?? new Collection();
    }

    /**
     * Резольвер URL аватара сотрудника.
     */
    private function resolveAvatarUrl(mixed $staffMember): ?string
    {
        return $staffMember->getAvatarUrl() ?? null;
    }
}
