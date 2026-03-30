<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EventPlanningService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private EventAIService $aiService,
            private WalletService $walletService,
            private FraudControlService $fraudService,
        ) {}

        /**
         * Создает полноценный проект события на базе AI-плана.
         */
        public function createEventProject(array $inputData, string $correlationId): Event
        {
            Log::channel('audit')->info("EventPlanningService: Creating event project", [
                'correlation_id' => $correlationId,
                'client_id' => $inputData['client_id'],
            ]);

            // 1. Предварительный Fraud Check (обязателен по канону 2026)
            $this->fraudService->check([
                'user_id' => $inputData['client_id'],
                'operation' => 'create_event',
                'amount' => $inputData['budget_rubles'],
                'correlation_id' => $correlationId,
            ]);

            return DB::transaction(function () use ($inputData, $correlationId) {

                // 2. Генерация AI-плана
                $aiPlan = $this->aiService->generateEventPlan(
                    $inputData['type'],
                    $inputData['preferences'] ?? [],
                    $correlationId
                );

                // 3. Создание основной модели Event
                $event = Event::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => $inputData['tenant_id'],
                    'business_group_id' => $inputData['business_group_id'] ?? null,
                    'client_id' => $inputData['client_id'],
                    'type' => $inputData['type'],
                    'title' => $inputData['title'],
                    'description' => $inputData['description'] ?? null,
                    'event_date' => $inputData['event_date'],
                    'location' => $inputData['location'],
                    'guest_count' => $inputData['guest_count'],
                    'status' => 'planning',
                    'is_b2b' => $inputData['is_b2b'] ?? false,
                    'total_budget_kopecks' => (int)($inputData['budget_rubles'] * 100),
                    'prepayment_kopecks' => (int)(($aiPlan['cancellation_policy']['prepayment_percent'] / 100) * ($inputData['budget_rubles'] * 100)),
                    'cancellation_fee_kopecks' => (int)($aiPlan['cancellation_policy']['non_refundable_deposit_rub'] * 100),
                    'ai_plan' => $aiPlan,
                    'cancellation_policy' => $aiPlan['cancellation_policy'],
                    'correlation_id' => $correlationId,
                ]);

                // 4. Наполнение бюджета элементами на основе AI-плана
                foreach ($aiPlan['budget_breakdown'] as $item) {
                    EventBudgetItem::create([
                        'event_id' => $event->id,
                        'tenant_id' => $event->tenant_id,
                        'category' => $item['category'],
                        'title' => "Оценочный бюджет: " . $item['category'],
                        'estimated_kopecks' => (int)($item['estimate'] * 100),
                        'actual_kopecks' => 0,
                        'correlation_id' => $correlationId,
                    ]);
                }

                Log::channel('audit')->info("EventPlanningService: Event project #{$event->uuid} created successfully", [
                    'correlation_id' => $correlationId,
                    'event_id' => $event->id,
                ]);

                return $event;

            }, attempts: 3);
        }

        /**
         * Контрактация вендора (Бронирование).
         */
        public function contractVendor(Event $event, array $vendorData, string $correlationId): EventVendor
        {
            Log::channel('audit')->info("EventPlanningService: Contracting vendor for event #{$event->uuid}", [
                'correlation_id' => $correlationId,
                'vendor_name' => $vendorData['vendor_name'],
                'vertical' => $vendorData['vertical'],
            ]);

            return DB::transaction(function () use ($event, $vendorData, $correlationId) {

                // 1. Создание связи с вендором
                $ev = EventVendor::create([
                    'event_id' => $event->id,
                    'tenant_id' => $event->tenant_id,
                    'vertical' => $vendorData['vertical'],
                    'vendor_id' => $vendorData['vendor_id'],
                    'vendor_name' => $vendorData['vendor_name'],
                    'status' => 'contracted',
                    'agreed_price_kopecks' => (int)($vendorData['price_rubles'] * 100),
                    'deposit_paid_kopecks' => 0,
                    'agreed_conditions' => $vendorData['conditions'] ?? null,
                    'correlation_id' => $correlationId,
                ]);

                // 2. Попытка списания депозита через Wallet (Hold)
                if ($ev->agreed_price_kopecks > 0) {
                    $depositAmount = (int)($ev->agreed_price_kopecks * 0.2); // 20% стандарт
                    $this->walletService->hold($event->client_id, $depositAmount, "Prepayment for {$ev->vendor_name}", $correlationId);
                    $ev->update(['deposit_paid_kopecks' => $depositAmount]);
                }

                return $ev;
            });
        }

        /**
         * Завершение события и финальный расчёт.
         */
        public function finishEvent(Event $event, string $correlationId): bool
        {
            if ($event->isCompleted()) return true;

            Log::channel('audit')->info("EventPlanningService: Completing event #{$event->uuid}", [
                'correlation_id' => $correlationId,
            ]);

            return DB::transaction(function () use ($event, $correlationId) {
                $event->update(['status' => 'completed']);

                // Здесь должна быть логика финальных оплат вендорам (Release Hold -> Debit)

                return true;
            });
        }

        /**
         * Отмена события с удержанием штрафа (Cancellation Policy 2026).
         */
        public function cancelEvent(Event $event, string $reason, string $correlationId): bool
        {
            Log::channel('audit')->warning("EventPlanningService: Cancelling event #{$event->uuid}", [
                'correlation_id' => $correlationId,
                'reason' => $reason,
            ]);

            return DB::transaction(function () use ($event, $correlationId) {
                $event->update(['status' => 'cancelled']);

                // Удержание штрафа (Cancellation Fee) - Канон 2026
                if ($event->cancellation_fee_kopecks > 0) {
                    $this->walletService->debit($event->client_id, $event->cancellation_fee_kopecks, "Cancellation fee for event #{$event->uuid}", $correlationId);
                }

                return true;
            });
        }
}
