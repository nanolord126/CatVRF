<?php declare(strict_types=1);

namespace App\Services;




use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;

final readonly class BaseDomainService
{

    /**
         * @var string
         */
        private string $correlationId;

        /**
         * @var \App\Services\FraudControlService
         */
        private FraudControlService $fraud;

        /**
         * Base constructor injecting core dependencies.
         */
        public function __construct(
        private readonly Request $request,FraudControlService $fraud,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
    )
        {
            $this->fraudControl = $fraudControl;
            $this->correlationId = $this->request->header('X-Correlation-ID') ?? (string) Str::uuid();
        }

        /**
         * Wrapper for critical operations requiring transactions and logs.
         */
        protected function executeTransaction(callable $operation, string $actionName, int $amount = 0): mixed
        {
            $userId = $this->guard->id() ?? 0;

            $this->fraud->check(
                $userId,
                $actionName,
                $amount,
                $this->request->ip(),
                $this->request->header('User-Agent'),
                $this->correlationId
            );

            $this->logger->channel('audit')->info("Service: $actionName started", [
                'correlation_id' => $this->correlationId,
                'user_id' => $this->guard->id(),
                'tenant_id' => $this->guard->user()?->tenant_id,
            ]);

            try {
                return $this->db->transaction(function () use ($operation, $actionName) {
                    $result = $operation($this->correlationId);

                    if ($result === null) {
                        throw new RuntimeException("Action '$actionName' returned null. Null returns are forbidden in Canon 2026.");
                    }

                    return $result;
                });
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error("Service: $actionName failed", [
                    'correlation_id' => $this->correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            } finally {
                $this->logger->channel('audit')->info("Service: $actionName completed", [
                    'correlation_id' => $this->correlationId,
                ]);
            }
        }
}
