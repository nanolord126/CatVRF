<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class RealEstateFraudService
{

    /**
         * КАНОН 2026: FraudMLService для недвижимости.
         *
         * Основная цель - выявление накруток, дубликатов объектов, "черных риелторов"
         * и подозрительных схем с кэшбэком или демпингом.
         */
        public function __construct(
            private readonly \App\Services\FraudControlService $fraud,
            private string $correlation_id = '', private readonly LoggerInterface $logger, private readonly Guard $guard
        ) {}

        /**
         * Скоринг сделки/объявления на предмет фрода.
         *
         * @param Property|Listing|B2BDeal $entity Объект для проверки
         * @return float ML-скор (0.0 - 1.0, 1.0 = 100% фрод)
         */
        public function scoreEntity(object $entity): float
        {
            $score = 0.0;
            $features = [];

            // 1. Проверка на дублирование адреса другим тенантом
            if ($entity instanceof Property) {
                $isDuplicate = Property::where('address', $entity->address)
                    ->where('tenant_id', '!=', $entity->tenant_id)
                    ->exists();
                if ($isDuplicate) { $score += 0.8; $features['duplicate_address'] = true; }
            }

            // 2. Аномальный демпинг цены (Listing)
            if ($entity instanceof Listing) {
                $avgPrice = Listing::where('type', $entity->type)
                    ->where('status', 'active')
                    ->avg('price');

                if ($entity->price < ($avgPrice * 0.4)) {
                    $score += 0.5;
                    $features['price_too_low'] = true;
                }
            }

            // 3. Проверка через глобальный FraudControl
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'real_estate_valuation', amount: 0, correlationId: $correlationId ?? '');

            if (!$globalCheck) {
                $score = 1.0;
            }

            $this->logger->info('Real Estate Fraud Score', [
                'entity_type' => get_class($entity),
                'entity_uuid' => $entity->uuid ?? 'N/A',
                'score' => $score,
                'features' => $features,
                'correlation_id' => $this->correlation_id,
            ]);

            return $score;
        }

        /**
         * Проверка контракта перед подписанием.
         */
        public function validateContractForFraud(RentalContract $contract): void
        {
            // Если сумма аренды 0 или подозрительно высокая - блок
            if ($contract->rent_amount <= 0 || $contract->rent_amount > 50000000) {
                throw new \RuntimeException('Подозрительные финансовые условия договора. Блокировка FraudML.');
            }

            // Проверка частоты сделок клиента (анти-фроуд)
            $recentContracts = RentalContract::where('client_id', $contract->client_id)
                ->where('created_at', '>=', now()->subHours(1))
                ->count();

            if ($recentContracts > 3) {
                throw new \DomainException('Превышен лимит создания договоров для одного клиента. Подозрение на спам.');
            }
        }
}
