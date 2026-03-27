<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Jobs;

use App\Domains\ShortTermRentals\Models\StrApartment;
use App\Domains\ShortTermRentals\Models\StrCalendarAvailability;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Синхронизация календаря (iCal / внешний провайдер / Avito)
 * 
 * Обязательно: Idempotency, correlation_id, audit log.
 */
final class StrCalendarSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $apartmentId,
        public readonly ?string $correlationId = null
    ) {}

    public function handle(): void
    {
        $correlationId = $this->correlationId ?? (string) Str::uuid();
        $apartment = StrApartment::findOrFail($this->apartmentId);

        Log::channel('audit')->info('ShortTermRental: iCal Sync Started', [
            'apartment_id' => $apartment->id,
            'correlation_id' => $correlationId,
        ]);

        // Симуляция получения данных из внешнего фида (например, Avito / Суточно.ру)
        // В реальном проекте здесь парсим iCal или API.
        
        $externalBlockDates = [
            now()->addDays(2)->format('Y-m-d'),
            now()->addDays(3)->format('Y-m-d'),
        ];

        foreach ($externalBlockDates as $dateString) {
            StrCalendarAvailability::updateOrCreate(
                [
                    'apartment_id' => $apartment->id,
                    'date' => $dateString,
                ],
                [
                    'is_available' => false,
                    'reason' => 'Бронь внешней системы (iCal Sync)',
                    'correlation_id' => $correlationId,
                ]
            );
        }

        Log::channel('audit')->info('ShortTermRental: iCal Sync Finished', [
            'apartment_id' => $apartment->id,
            'blocked_dates_count' => count($externalBlockDates),
            'correlation_id' => $correlationId,
        ]);
    }

    public function tags(): array
    {
        return ['short-term-rentals', 'sync', "apartment:{$this->apartmentId}"];
    }
}
