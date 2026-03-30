<?php declare(strict_types=1);

namespace App\Domains\WeddingPlanning\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GenerateWeddingContractOnBookingListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use InteractsWithQueue;

        public function handle(object $event): void
        {
            $booking = $event->booking;
            $correlationId = $event->correlationId ?? (string) Str::uuid();

            Log::channel('audit')->info('Generating wedding contract [Listener Start]', [
                'booking_id' => $booking->id,
                'correlation_id' => $correlationId,
            ]);

            try {
                // Предотвращение дублирования
                $existingContract = WeddingContract::where('booking_id', $booking->id)->first();
                if ($existingContract) {
                    Log::channel('audit')->info('Contract already exists for booking', [
                        'booking_id' => $booking->id,
                        'contract_id' => $existingContract->id,
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

                Log::channel('audit')->info('Wedding contract successfully generated (Draft)', [
                    'contract_id' => $contract->id,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to generate wedding contract', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                throw $e;
            }
        }
}
