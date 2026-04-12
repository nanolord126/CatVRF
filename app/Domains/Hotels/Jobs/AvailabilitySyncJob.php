<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Jobs;


use App\Domains\Hotels\Services\HotelAvailabilityService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * AvailabilitySyncJob — Синхронизация наличия номеров из внешних систем.
 *
 * Получает данные из внешнего провайдера (Channel Manager, OTA API)
 * и синхронизирует остатки номеров в InventoryService.
 * Запускается по расписанию или по webhook от провайдера.
 *
 * ВАЖНО: LoggerInterface нельзя хранить в serialized Job,
 * поэтому логгер резолвится в handle() через DI.
 *
 * @package App\Domains\Hotels\Jobs
 */
final class AvailabilitySyncJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Максимальное количество попыток выполнения.
     */
    public int $tries = 3;

    /**
     * Таймаут в секундах.
     */
    public int $timeout = 120;

    /**
     * @param int    $hotelId       Идентификатор отеля для синхронизации
     * @param array  $syncData      Массив данных синхронизации [{room_id, stock, source}]
     * @param string $correlationId Идентификатор корреляции для трейсинга
     */
    public function __construct(
        private readonly int $hotelId,
        private readonly array $syncData,
        private readonly string $correlationId,
    ) {}

    /**
     * Выполняет синхронизацию остатков номеров.
     * Логгер и сервис получаются через constructor injection в handle().
     */
    public function handle(
        HotelAvailabilityService $availabilityService,
        LoggerInterface $logger,
    ): void {
        $logger->info('Hotel Availability Sync Started', [
            'hotel_id'       => $this->hotelId,
            'rooms_count'    => count($this->syncData),
            'correlation_id' => $this->correlationId,
            'started_at'     => Carbon::now()->toIso8601String(),
        ]);

        $synced  = 0;
        $failed  = 0;

        foreach ($this->syncData as $roomSync) {
            try {
                $roomId  = (int) ($roomSync['room_id'] ?? 0);
                $newStock = (int) ($roomSync['stock'] ?? 0);
                $source  = (string) ($roomSync['source'] ?? 'unknown');

                $availabilityService->syncRoomStock(
                    $roomId,
                    $newStock,
                    sprintf('External Sync: %s', $source),
                );

                $synced++;
            } catch (\Throwable $e) {
                $failed++;

                $logger->error('Room stock sync failed', [
                    'hotel_id'       => $this->hotelId,
                    'room_sync'      => $roomSync,
                    'error'          => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
            }
        }

        $logger->info('Hotel Availability Sync Completed', [
            'hotel_id'       => $this->hotelId,
            'synced'         => $synced,
            'failed'         => $failed,
            'correlation_id' => $this->correlationId,
            'finished_at'    => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * Теги для мониторинга в Horizon.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'hotel',
            'inventory',
            'sync',
            'hotel_id:' . $this->hotelId,
        ];
    }

    /**
     * Определяет уникальный идентификатор задачи.
     */
    public function uniqueId(): string
    {
        return sprintf('availability_sync_%d_%s', $this->hotelId, $this->correlationId);
    }

    /**
     * Отладочный массив.
     *
     * @return array<string, mixed>
     */
    public function toDebugArray(): array
    {
        return [
            'class'          => static::class,
            'hotel_id'       => $this->hotelId,
            'rooms_count'    => count($this->syncData),
            'correlation_id' => $this->correlationId,
            'timestamp'      => Carbon::now()->toIso8601String(),
        ];
    }
}
