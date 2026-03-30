<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BaseDomainService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * @var string
         */
        protected readonly string $correlationId;

        /**
         * @var \App\Services\FraudControlService
         */
        protected readonly FraudControlService $fraudControl;

        /**
         * Base constructor injecting core dependencies.
         */
        public function __construct(FraudControlService $fraudControl)
        {
            $this->fraudControl = $fraudControl;
            $this->correlationId = request()->header('X-Correlation-ID') ?? (string) Str::uuid();
        }

        /**
         * Wrapper for critical operations requiring transactions and logs.
         */
        protected function executeTransaction(callable $operation, string $actionName, int $amount = 0): mixed
        {
            $userId = auth()->id() ?? 0;

            $this->fraudControl->check(
                $userId,
                $actionName,
                $amount,
                request()->ip(),
                request()->header('User-Agent'),
                $this->correlationId
            );

            Log::channel('audit')->info("Service: $actionName started", [
                'correlation_id' => $this->correlationId,
                'user_id' => auth()->id(),
                'tenant_id' => auth()->user()?->tenant_id,
            ]);

            try {
                return DB::transaction(function () use ($operation, $actionName) {
                    $result = $operation($this->correlationId);

                    if ($result === null) {
                        throw new RuntimeException("Action '$actionName' returned null. Null returns are forbidden in Canon 2026.");
                    }

                    return $result;
                });
            } catch (\Throwable $e) {
                Log::channel('audit')->error("Service: $actionName failed", [
                    'correlation_id' => $this->correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            } finally {
                Log::channel('audit')->info("Service: $actionName completed", [
                    'correlation_id' => $this->correlationId,
                ]);
            }
        }
}
