<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

final class StrCalendarSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $apartmentId,
        private readonly ?string $correlationId,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(): void
    {
        $correlationId = $this->correlationId ?? (string) Str::uuid();
        $apartment = StrApartment::findOrFail($this->apartmentId);

        $this->logger->info('ShortTermRental: iCal Sync Started', [
            'apartment_id' => $apartment->id,
            'correlation_id' => $correlationId,
        ]);

        // Симуляция получения данных из внешнего фида (например, Avito / Суточно.ру)
        // В реальном проекте здесь парсим iCal или API.

        $externalBlockDates = [
            CarbonImmutable::now()->addDays(2)->format('Y-m-d'),
            CarbonImmutable::now()->addDays(3)->format('Y-m-d'),
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

        $this->logger->info('ShortTermRental: iCal Sync Finished', [
            'apartment_id' => $apartment->id,
            'blocked_dates_count' => iterator_count($externalBlockDates),
            'correlation_id' => $correlationId,
        ]);
    }

    public function tags(): array
    {
        return ['short-term-rentals', 'sync', "apartment:{$this->apartmentId}"];
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->error('shorttermrentals job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}