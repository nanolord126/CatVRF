<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Events;

use App\Domains\Hotels\Models\Booking;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * BookingCancelled — Событие отмены бронирования.
 *
 * Публикуется после успешной отмены бронирования (статус → cancelled).
 * Содержит полный контекст для аудита и компенсационных действий:
 * бронирование, причина отмены, correlation_id.
 *
 * @package App\Domains\Hotels\Events
 */
final readonly class BookingCancelled
{

    /**
     * @param Booking $booking       Отменённое бронирование
     * @param string  $reason        Причина отмены
     * @param string  $correlationId Идентификатор корреляции для трейсинга
     */
    public function __construct(
        public Booking $booking,
        public string $reason,
        public string $correlationId,
    ) {}

    /**
     * Возвращает данные события для широковещательной рассылки.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'booking_id'     => $this->booking->uuid,
            'hotel_id'       => $this->booking->hotel_id,
            'tenant_id'      => $this->booking->tenant_id,
            'status'         => 'cancelled',
            'reason'         => $this->reason,
            'correlation_id' => $this->correlationId,
        ];
    }

    /**
     * Название канала широковещания.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'hotel.booking.cancelled';
    }

    /**
     * Строковое представление события.
     */
    public function __toString(): string
    {
        return sprintf(
            '%s[booking=%s, reason=%s, correlation=%s]',
            static::class,
            $this->booking->uuid ?? 'N/A',
            $this->reason,
            $this->correlationId,
        );
    }

    /**
     * Отладочный массив для логирования и инспекции.
     *
     * @return array<string, mixed>
     */
    public function toDebugArray(): array
    {
        return [
            'class'          => static::class,
            'booking_uuid'   => $this->booking->uuid,
            'tenant_id'      => $this->booking->tenant_id,
            'reason'         => $this->reason,
            'correlation_id' => $this->correlationId,
            'timestamp'      => Carbon::now()->toIso8601String(),
        ];
    }
}
