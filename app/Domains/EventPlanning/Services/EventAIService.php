<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Services;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
final readonly class EventAIService
{

    public function __construct(
            private AIConstructorService $aiBase, private readonly LoggerInterface $logger) {}

        /**
         * Генерирует предварительный план события.
         *
         * @param string $eventType wedding, corporate, birthday
         * @param array $preferences Пожелания пользователя (стиль, локация, бюджет)
         * @param string $correlationId
         * @return array План в формате JSON
         */
        public function generateEventPlan(string $eventType, array $preferences, string $correlationId): array
        {
            $this->logger->info("EventAIService: Generating plan for {$eventType}", [
                'correlation_id' => $correlationId,
                'preferences' => $preferences,
            ]);

            // Эмулируем сложную AI-логику для "LURID MODE" 2026
            // В реальности здесь был бы вызов OpenAI/GigaChat через $this->aiBase

            $budget = $preferences['budget_rubles'] ?? 100000;
            $guests = $preferences['guests'] ?? 20;

            $plan = [
                'overview' => [
                    'type' => $eventType,
                    'title' => "Эксклюзивный " . $this->getTypeLabel($eventType),
                    'timeline' => "6-8 часов",
                    'complexity' => $this->calculateComplexity($guests, $budget),
                ],
                'timeline' => [
                    ['time' => '10:00', 'activity' => 'Прибытие вендоров (Кейтеринг, Декор)'],
                    ['time' => '15:00', 'activity' => 'Встреча гостей'],
                    ['time' => '16:00', 'activity' => 'Начало основной программы'],
                    ['time' => '22:00', 'activity' => 'Завершение события и демонтаж'],
                ],
                'recommended_vendors' => $this->suggestVendorsByVerticals($eventType, $budget),
                'budget_breakdown' => $this->calculateBudgetBreakdown($budget),
                'cancellation_rules' => $this->generateCancellationPolicy($eventType, $budget),
                'ai_score' => 0.98,
                'generated_at' => Carbon::now()->toIso8601String(),
            ];

            $this->logger->info("EventAIService: Plan generated successfully", [
                'correlation_id' => $correlationId,
                'plan_title' => $plan['overview']['title'],
            ]);

            return $plan;
        }

        /**
         * Подбирает вертикали для интеграции.
         */
        private function suggestVendorsByVerticals(string $type, float $budget): array
        {
            $vendors = [
                ['vertical' => 'food', 'role' => 'Кейтеринг/Ресторан', 'weight' => 0.4 * $budget],
                ['vertical' => 'beauty', 'role' => 'Стилист/Визажист', 'weight' => 0.1 * $budget],
                ['vertical' => 'photo', 'role' => 'Фотограф/Оператор', 'weight' => 0.15 * $budget],
            ];

            if ($type === 'wedding') {
                $vendors[] = ['vertical' => 'auto', 'role' => 'Свадебный кортеж', 'weight' => 0.1 * $budget];
                $vendors[] = ['vertical' => 'decoration', 'role' => 'Оформление зала', 'weight' => 0.25 * $budget];
            }

            return $vendors;
        }

        /**
         * Расчет детализации бюджета.
         */
        private function calculateBudgetBreakdown(float $totalRub): array
        {
            return [
                ['category' => 'Venue & Food', 'estimate' => $totalRub * 0.5, 'required' => true],
                ['category' => 'Entertainment', 'estimate' => $totalRub * 0.2, 'required' => true],
                ['category' => 'Media (Photo/Video)', 'estimate' => $totalRub * 0.15, 'required' => false],
                ['category' => 'Logistics', 'estimate' => $totalRub * 0.1, 'required' => false],
                ['category' => 'Reserve', 'estimate' => $totalRub * 0.05, 'required' => true],
            ];
        }

        /**
         * Генерация правил отмены (Financial Safeguard).
         */
        private function generateCancellationPolicy(string $type, float $budget): array
        {
            return [
                'prepayment_percent' => $budget > 500000 ? 50 : 30,
                'refundable_period_days' => 14,
                'non_refundable_deposit_rub' => min($budget * 0.1, 50000),
                'forced_cancellation_fee' => '15%',
                'description' => "При отмене менее чем за 7 дней удерживается 'non_refundable_deposit' + фактически понесенные расходы вендоров.",
            ];
        }

        private function getTypeLabel(string $type): string
        {
            return match($type) {
                'corporate' => 'Корпоратив',
                'birthday' => 'День рождения',
                default => 'Праздник',
            };
        }

        private function calculateComplexity(int $guests, float $budget): string
        {
            if ($guests > 200 || $budget > 2000000) return 'Very High (Premium)';
            if ($guests > 50 || $budget > 500000) return 'Medium High';
            return 'Standard';
        }
}
