<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Application\Services;

use Illuminate\Cache\CacheManager;
use Psr\Log\LoggerInterface;

/**
 * Application Service: Handles cache invalidation for Beauty entities
 */
final readonly class CacheInvalidationService
{
    public function __construct(
        private readonly CacheManager $cache,
        private readonly LoggerInterface $logger,
    ) {}
    public function invalidateAppointmentCache(string $appointmentId, string $userId, string $salonId): void
    {
        try {
            $this->cache->tags(['beauty', 'appointments', "appointment:{$appointmentId}"])->flush();
            $this->cache->tags(['beauty', 'users', "user:{$userId}", "user:{$userId}:appointments"])->flush();
            $this->cache->tags(['beauty', 'salons', "salon:{$salonId}", "salon:{$salonId}:appointments"])->flush();

            $this->logger->$this->logger->info('Cache invalidated for beauty appointment', [
                'appointment_id' => $appointmentId,
                'user_id' => $userId,
                'salon_id' => $salonId,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to invalidate cache for beauty appointment', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function invalidateSalonCache(string $salonId): void
    {
        $this->cache->tags(['beauty', 'salons', "salon:{$salonId}"])->flush();
    }

    public function invalidateMasterCache(string $masterId): void
    {
        $this->cache->tags(['beauty', 'masters', "master:{$masterId}"])->flush();
    }
}
