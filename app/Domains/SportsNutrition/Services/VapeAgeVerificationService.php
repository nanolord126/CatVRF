<?php declare(strict_types=1);

namespace App\Domains\SportsNutrition\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class VapeAgeVerificationService
{

    /**
         * Конструктор с DP зависимостями.
         */
        public function __construct(private FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Инициировать проверку возраста через выбранный метод.
         *
         * @param string $method esia, ebs, sber_id, t_id
         */
        public function initiateVerification(int $userId, string $method, string $correlationId = null): string
        {
            $correlationId ??= (string) Str::uuid();

            $this->logger->info('Age verification started for vapes', [
                'user_id' => $userId,
                'method' => $method,
                'correlation_id' => $correlationId,
            ]);

            return $this->db->transaction(function () use ($userId, $method, $correlationId) {

                // 1. Создаем запись верификации со статусом 'pending'
                $verification = VapeAgeVerification::create([
                    'user_id' => $userId,
                    'method' => $method,
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                ]);

                // 2. Fraud Check инициации проверки
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'vape_age_verify_init', amount: 0, correlationId: $correlationId ?? '');

                return $verification->uuid;
            });
        }

        /**
         * Завершение верификации (Callback от внешнего провайдера).
         * Обрабатывает ответ от Госуслуг/Банков и устанавливает финальный статус.
         */
        public function completeVerification(string $uuid, array $providerData, string $correlationId = null): bool
        {
            $correlationId ??= (string) Str::uuid();

            return $this->db->transaction(function () use ($uuid, $providerData, $correlationId) {

                $verification = VapeAgeVerification::where('uuid', $uuid)->lockForUpdate()->firstOrFail();

                // 3. Анализ данных от провайдера (имитация парсинга BirthDate)
                $birthDateStr = $providerData['birth_date'] ?? null;
                $isAdult = false;

                if ($birthDateStr) {
                    $birthDate = Carbon::parse($birthDateStr);
                    $isAdult = $birthDate->diffInYears(now()) >= 18;
                }

                // 4. Обновление статуса
                $status = $isAdult ? 'verified' : 'rejected';

                $verification->update([
                    'status' => $status,
                    'birth_date' => $birthDate ?? null,
                    'verified_at' => $isAdult ? now() : null,
                    'provider_response' => $providerData,
                    'external_id' => $providerData['external_id'] ?? null,
                ]);

                $this->logger->info('Age verification completed for vapes', [
                    'user_id' => $verification->user_id,
                    'status' => $status,
                    'is_adult' => $isAdult,
                    'correlation_id' => $correlationId,
                    'verification_uuid' => $uuid,
                ]);

                return $isAdult;
            });
        }

        /**
         * Проверка: имеет ли пользователь актуальную подтвержденную верификацию.
         */
        public function hasAValidVerification(int $userId): bool
        {
            return VapeAgeVerification::where('user_id', $userId)
                ->where('status', 'verified')
                ->whereNotNull('verified_at')
                ->exists();
        }
}
