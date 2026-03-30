<?php declare(strict_types=1);

namespace App\Domains\SportsNutrition\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VapeAgeVerificationController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Конструктор с DP зависимостью (VapeAgeVerificationService).
         */
        public function __construct(
            private VapeAgeVerificationService $ageVerifier,
        ) {}

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
                    userId: auth()->id(),
                    method: $method,
                    correlationId: $correlationId,
                );

                // 2. Audit log
                Log::channel('audit')->info('Vape verification initiate controller', [
                    'user_id' => auth()->id(),
                    'method' => $method,
                    'verification_uuid' => $uuid,
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'success' => true,
                    'verification_uuid' => $uuid,
                    'correlation_id' => $correlationId,
                ]);

            } catch (Throwable $e) {

                // 3. Error Log + Trace
                Log::channel('audit')->error('Vape verification init failure', [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
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
                Log::channel('audit')->info('Vape verification complete controller results', [
                    'verification_uuid' => $uuid,
                    'is_adult' => $isAdult,
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'success' => true,
                    'is_adult' => $isAdult,
                    'correlation_id' => $correlationId,
                ]);

            } catch (Throwable $e) {

                Log::channel('audit')->error('Vape verification complete failure', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
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
            $correlationId = request()->header('X-Correlation-ID') ?? (string) Str::uuid();

            $isVerified = $this->ageVerifier->hasAValidVerification(auth()->id());

            return response()->json([
                'success' => true,
                'is_verified' => $isVerified,
                'correlation_id' => $correlationId,
            ]);
        }
}
