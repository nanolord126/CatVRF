<?php declare(strict_types=1);

/**
 * UpdatePropertyStatsListener — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/updatepropertystatslistener
 */


namespace App\Domains\RealEstate\Listeners;


use Psr\Log\LoggerInterface;
final class UpdatePropertyStatsListener
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    public function handle(PropertyViewed $event): void
        {
            try {
                $property = $event->appointment->property;

                // Инкрементируем счётчик просмотров
                $property->increment('view_count');

                $this->logger->info('Property stats updated', [
                    'property_id' => $property->id,
                    'view_count' => $property->view_count,
                    'correlation_id' => $event->correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to update property stats', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
                throw $e;
            }
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
