<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;


use Psr\Log\LoggerInterface;
final class StrCalendarSyncJob
{


    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        public function __construct(
            private readonly int $apartmentId,
            private ?string $correlationId = null, private readonly LoggerInterface $logger
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

            $this->logger->info('ShortTermRental: iCal Sync Finished', [
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

