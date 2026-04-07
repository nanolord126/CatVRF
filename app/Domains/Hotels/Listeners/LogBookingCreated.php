<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Listeners;

use App\Domains\Hotels\Events\BookingCreated;
use App\Services\AuditService;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;

/**
 * LogBookingCreated — Слушатель события создания бронирования.
 *
 * Записывает аудит-лог при каждом создании бронирования.
 * Использует AuditService для централизованного логирования
 * и LoggerInterface для оперативного канала.
 *
 * @package App\Domains\Hotels\Listeners
 */
final readonly class LogBookingCreated
{
    /**
     * @param LoggerInterface $logger Логгер для оперативных записей
     * @param AuditService    $audit  Централизованный сервис аудита
     */
    public function __construct(
        private LoggerInterface $logger,
        private AuditService $audit,
    ) {}

    /**
     * Обрабатывает событие создания бронирования.
     *
     * Записывает данные бронирования в аудит-лог и канал security
     * для мониторинга подозрительных паттернов.
     */
    public function handle(BookingCreated $event): void
    {
        $booking = $event->booking;
        $correlationId = $event->correlationId;

        $this->logger->info('Hotel Booking Created Audit', [
            'booking_uuid'   => $booking->uuid,
            'hotel_id'       => $booking->hotel_id,
            'tenant_id'      => $booking->tenant_id,
            'status'         => $booking->status,
            'correlation_id' => $correlationId,
            'logged_at'      => Carbon::now()->toIso8601String(),
        ]);

        $this->audit->log(
            'booking_created',
            $booking::class,
            $booking->id,
            [],
            $booking->toArray(),
            $correlationId,
        );
    }

    /**
     * Определяет, должен ли слушатель быть поставлен в очередь.
     */
    public function shouldQueue(): bool
    {
        return false;
    }

    /**
     * Строковое представление слушателя.
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Отладочный массив.
     *
     * @return array<string, mixed>
     */
    public function toDebugArray(): array
    {
        return [
            'class'     => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
