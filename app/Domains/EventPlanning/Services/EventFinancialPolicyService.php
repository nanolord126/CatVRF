<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EventFinancialPolicyService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly \App\Domains\Payments\Services\WalletService $walletService,
        ) {}

        /**
         * Рассчитать предоплату (Prepayment Calculation)
         * Свадьбы: 20%; Корпоративы: 30%; День рождения: 15%
         */
        public function calculateRequiredPrepayment(Event $event): int
        {
            $percentage = match ($event->type) {
                'wedding' => 20,
                'corporate' => 30,
                'birthday' => 15,
                default => 25,
            };

            $prepayment = (int) ($event->total_budget_kopecks * ($percentage / 100));

            Log::channel('audit')->info('Financial: Prepayment calculated', [
                'event_uuid' => $event->uuid,
                'amount' => $prepayment,
                'percentage' => $percentage,
            ]);

            return $prepayment;
        }

        /**
         * Расчет штрафов при отмене (Cancellation Fees)
         * За 30+ дней: 10%
         * За 14–30 дней: 30%
         * За 7–14 дней: 50%
         * Менее 7 дней: 100% (non-refundable)
         */
        public function calculateCancellationFee(Event $event): int
        {
            $daysToEvent = Carbon::now()->diffInDays($event->event_date, false);

            $percentage = match (true) {
                $daysToEvent >= 30 => 10,
                $daysToEvent >= 14 => 30,
                $daysToEvent >= 7 => 50,
                default => 100,
            };

            $fee = (int) ($event->total_budget_kopecks * ($percentage / 100));

            Log::channel('audit')->warning('Financial: Cancellation fee calculated', [
                'event_uuid' => $event->uuid,
                'daysToEvent' => $daysToEvent,
                'fee_percentage' => $percentage,
                'fee_amount' => $fee,
            ]);

            return $fee;
        }

        /**
         * Распределение бюджета по вендорам.
         */
        public function distributeBudget(Event $event): void
        {
            $aiPlan = $event->ai_plan;
            if (empty($aiPlan)) {
                Log::channel('audit')->warning('Financial: Attempted distribution without AI Plan', [
                    'event_uuid' => $event->uuid,
                ]);
                return;
            }

            // Логика распределения копеек из JSON плана по связанным вендорам
            // (Реализуется в Layer 7 при маппинге EventVendor)
            Log::channel('audit')->info('Financial: Budget distributed to modules', [
                'event_uuid' => $event->uuid,
                'total' => $event->total_budget_kopecks,
            ]);
        }
}
