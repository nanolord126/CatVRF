<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Log\LogManager;
use Illuminate\Contracts\Routing\ResponseFactory;

final class TwoFactorAuthentication
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly ResponseFactory $response,
    ) {}



    /**
         * Проверяет 2FA и историю устройств.
         *
         * @param Request $request
         * @param Closure $next
         * @return mixed
         * @throws Exception
         */
        public function handle(Request $request, Closure $next): mixed
        {
            $user = $request->user();

            if (!$user) {
                return $next($request);
            }

            try {
                $deviceId = $this->getDeviceFingerprint($request);
                $correlationId = $request->header('X-Correlation-ID', '');

                // Проверяем известное устройство
                $knownDevice = DeviceHistory::where('user_id', $user->id)
                    ->where('device_fingerprint', $deviceId)
                    ->where('is_verified', true)
                    ->exists();

                if (!$knownDevice) {
                    // Новое устройство — требуем 2FA
                    if (!session('two_factor_verified')) {
                        $this->logger->channel('audit')->warning('Попытка доступа с неверифицированного устройства', [
                            'user_id' => $user->id,
                            'device_id' => $deviceId,
                            'correlation_id' => $correlationId,
                        ]);

                        return $this->response->json([
                            'error' => '2FA требуется',
                            'code' => 'two_factor_required',
                        ], 403);
                    }

                    // После верификации 2FA — сохраняем устройство
                    if (session('two_factor_verified_for_device') === $deviceId) {
                        DeviceHistory::create([
                            'user_id' => $user->id,
                            'device_fingerprint' => $deviceId,
                            'user_agent' => $request->header('User-Agent'),
                            'ip_address' => $request->ip(),
                            'is_verified' => true,
                            'verified_at' => now(),
                            'correlation_id' => $correlationId,
                        ]);
                    }
                }

                return $next($request);
            } catch (Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                $this->logger->channel('audit')->error('Ошибка при проверке 2FA', [
                    'user_id' => $user->id ?? null,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return $this->response->json([
                    'error' => 'Ошибка аутентификации',
                ], 500);
            }
        }

        /**
         * Генерирует отпечаток устройства.
         *
         * @param Request $request
         * @return string
         */
        private function getDeviceFingerprint(Request $request): string
        {
            $userAgent = $request->header('User-Agent') ?? '';
            $acceptLanguage = $request->header('Accept-Language') ?? '';

            return hash('sha256', "{$userAgent}|{$acceptLanguage}");
        }
}
