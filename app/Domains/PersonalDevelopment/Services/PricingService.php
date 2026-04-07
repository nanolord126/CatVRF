<?php declare(strict_types=1);

namespace App\Domains\PersonalDevelopment\Services;


use Psr\Log\LoggerInterface;
final readonly class PricingService
{

    /**
         * Конструктор с зависимостями.
         */
        private readonly string $correlationId;

        public function __construct(
            private readonly WalletService $walletService,
            string $correlationId = '', private readonly LoggerInterface $logger
        ) {
            $this->correlationId = $this->correlationId ?: (string) Str::uuid();
        }

        /**
         * Рассчитать финальную цену программы для пользователя.
         *
         * @param Program $program
         * @param \App\Models\User $user
         * @return int Копейки
         */
        public function calculateFinalPrice(Program $program, \App\Models\User $user): int
        {
            $basePrice = $program->price_kopecks;

            // B2B скидка (если пользователь сотрудник компании с подпиской)
            if ($program->is_corporate && $this->isB2BUser($user)) {
                $discount = (int)($basePrice * 0.15); // Корпоративная скидка 15%
                $finalPrice = $basePrice - $discount;
            } else {
                // B2C: Спец-предложения по промокодам (PromoCampaign integration)
                $finalPrice = $basePrice;
            }

            $this->logger->info('PD Pricing: Final price calculated', [
                'program_uuid' => $program->uuid,
                'user_id' => $user->id,
                'base_price' => $basePrice,
                'final_price' => $finalPrice,
                'correlation_id' => $this->correlationId,
            ]);

            return max($finalPrice, 0);
        }

        /**
         * Проверить, является ли пользователь корпоративным.
         */
        private function isB2BUser(\App\Models\User $user): bool
        {
            // В 2026 это проверяется через метаданные или принадлежность к BusinessGroup
            return (bool)($user->metadata['is_b2b'] ?? false);
        }

        /**
         * Рассчитать комиссию платформы (Standard: 14%).
         */
        public function calculateCommission(int $amount): int
        {
            return (int)($amount * 0.14);
        }
}
