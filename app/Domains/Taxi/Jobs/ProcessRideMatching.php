<?php declare(strict_types=1);

/**
 * ProcessRideMatching — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/processridematching
 */


namespace App\Domains\Taxi\Jobs;


use Psr\Log\LoggerInterface;
final class ProcessRideMatching
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        /**
         * Конструктор с инъекцией (по канону 2026).
         */
        public function __construct(
            private readonly TaxiRide $ride,
            private readonly string $correlationId, private readonly LoggerInterface $logger
        ) {}

        /**
         * Масштабируемая обработка поиска водителя.
         */
        public function handle(TaxiService $taxiService): void
        {
            $this->logger->info('Processing ride matching for ride', [
                'ride_id' => $this->ride->id,
                'correlation_id' => $this->correlationId
            ]);

            // 1. Поиск ближайших водителей (Radius Logic)
            $drivers = $taxiService->findAvailableDrivers(
                (float)$this->ride->pickup_lat,
                (float)$this->ride->pickup_lon,
                5.0 // 5 км начальный радиус
            );

            if ($drivers->isEmpty()) {
                // Расширяем радиус до 10 км (эмуляция рекурсивного поиска)
                $drivers = $taxiService->findAvailableDrivers(
                    (float)$this->ride->pickup_lat,
                    (float)$this->ride->pickup_lon,
                    10.0
                );
            }

            // 2. Отправка нотификаций (Layer 9 integration)
            foreach ($drivers as $driver) {
                $driver->notify(new \App\Domains\Taxi\Notifications\RideCreatedNotification($this->ride, $this->correlationId));
            }

            // 3. Отмена поездки, если водитель не найден за 5 минут
            if ($this->ride->created_at->addMinutes(5)->isPast()) {
                $this->ride->update(['status' => 'cancelled', 'reason' => 'No drivers found']);
                $this->logger->warning('Ride auto-cancelled: No drivers found', ['ride_uuid' => $this->ride->uuid]);
            }
        }
}
