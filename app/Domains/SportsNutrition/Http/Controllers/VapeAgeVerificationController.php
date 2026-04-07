<?php declare(strict_types=1);

namespace App\Domains\SportsNutrition\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class VapeAgeVerificationController extends Controller
{

    /**
         * Конструктор с DP зависимостью (VapeAgeVerificationService).
         */
        public function __construct(
            private VapeAgeVerificationService $ageVerifier, private readonly LoggerInterface $logger) {}

        /**
         * Инициировать верификацию через ЕСИА/Банк.
         */
        public function initiate(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

            try {
                $method = $request->validate([
                    'method' => ['required', 'string', 'in:esia,ebs,sber_id,t_id'],
                ])['method'];

                // 1. Создаем верификацию в ожидании
                $uuid = $this->ageVerifier->initiateVerification(
                    userId: $request->user()?->id,
                    method: $method,
                    correlationId: $correlationId,
                );

                // 2. Audit log
                $this->logger->info('Vape verification initiate controller', [
                    'user_id' => $request->user()?->id,
                    'method' => $method,
                    'verification_uuid' => $uuid,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'verification_uuid' => $uuid,
                    'correlation_id' => $correlationId,
                ]);

            } catch (Throwable $e) {

                // 3. Error Log + Trace
                $this->logger->error('Vape verification init failure', [
                    'user_id' => $request->user()?->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Internal error initiating age verification: ' . $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        /**
         * Callback от провайдера верификации (ЕСИА/Банк).
         * Обычно вызывается фронтендом после успешного редиректа.
         */
        public function complete(Request $request, string $uuid): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

            try {
                $providerData = $request->all(); // В реальном случае валидация подписи JWT ЕСИА

                // 4. Завершаем верификацию в сервисе
                $isAdult = $this->ageVerifier->completeVerification(
                    uuid: $uuid,
                    providerData: $providerData,
                    correlationId: $correlationId,
                );

                // 5. Audit log
                $this->logger->info('Vape verification complete controller results', [
                    'verification_uuid' => $uuid,
                    'is_adult' => $isAdult,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'is_adult' => $isAdult,
                    'correlation_id' => $correlationId,
                ]);

            } catch (Throwable $e) {

                $this->logger->error('Vape verification complete failure', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 400);
            }
        }

        /**
         * Проверка: верифицирован ли текущий пользователь.
         */
        public function check(): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

            $isVerified = $this->ageVerifier->hasAValidVerification($request->user()?->id);

            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'is_verified' => $isVerified,
                'correlation_id' => $correlationId,
            ]);
        }
}
