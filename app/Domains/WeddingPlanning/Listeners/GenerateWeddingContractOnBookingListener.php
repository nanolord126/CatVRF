<?php declare(strict_types=1);

namespace App\Domains\WeddingPlanning\Listeners;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final class GenerateWeddingContractOnBookingListener
{
    public function __construct(
        private readonly Request $request, private readonly LoggerInterface $logger) {}



        public function handle(object $event): void
        {
            $booking = $event->booking;
            $correlationId = $event->correlationId ?? (string) Str::uuid();

            $this->logger->info('Generating wedding contract [Listener Start]', [
                'booking_id' => $booking->id,
                'correlation_id' => $correlationId,
            ]);

            try {
                // Предотвращение дублирования
                $existingContract = WeddingContract::where('booking_id', $booking->id)->first();
                if ($existingContract) {
                    $this->logger->info('Contract already exists for booking', [
                        'booking_id' => $booking->id,
                        'contract_id' => $existingContract->id,
                        'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                    ]);
                    return;
                }

                // Создание контракта (Draft) согласно Layer 0/1/2
                $contract = new WeddingContract();
                $contract->wedding_event_id = $booking->wedding_event_id;
                $contract->booking_id = $booking->id;
                $contract->tenant_id = $booking->tenant_id;
                $contract->uuid = (string) Str::uuid();
                $contract->correlation_id = $correlationId;
                $contract->status = 'draft';

                // Заполнение условий из метаданных букинга/вендора
                $contract->terms_json = [
                    'prepayment_percent' => round(($booking->prepayment_amount / max($booking->amount, 1)) * 100, 2),
                    'total_amount' => $booking->amount,
                    'cancellation_policy' => '24h full refund, else 50% loss', // Согласно канону Wedding Planning
                    'contract_type' => 'b2b_service_agreement',
                ];

                $contract->save();

                $this->logger->info('Wedding contract successfully generated (Draft)', [
                    'contract_id' => $contract->id,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to generate wedding contract', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                throw $e;
            }
        }
}
