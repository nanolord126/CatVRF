<?php

declare(strict_types=1);

namespace Modules\Commissions\Infrastructure\Services;

use App\Services\Finances\FinancesService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

final class FinancesIntegrationService
{
    public function __construct(private readonly FinancesService $financesService)
    {
    }

    /**
     * Records a commission transaction in the external Finances service.
     * Implements a retry mechanism for transient network errors.
     *
     * @param int $tenantId
     * @param int $amount
     * @param string $correlationId
     * @throws \Throwable
     */
    public function recordCommission(int $tenantId, int $amount, string $correlationId): void
    {
        try {
            // This is a placeholder for the actual integration logic.
            // In a real-world scenario, this would call an external service.
            // The retry logic is useful for handling transient network issues.
            $response = Http::retry(3, 100, function ($exception, $request) {
                return $exception instanceof RequestException && $exception->response->serverError();
            })->post(config('services.finances.endpoint') . '/transactions', [
                'tenant_id' => $tenantId,
                'amount' => -$amount, // Commission is an expense for the tenant
                'type' => 'commission',
                'description' => 'Platform commission',
                'correlation_id' => $correlationId,
            ]);

            $response->throw();

            Log::channel('audit')->info('Commission recorded in Finances.', [
                'tenant_id' => $tenantId,
                'amount' => $amount,
                'correlation_id' => $correlationId,
                'response_status' => $response->status(),
            ]);

        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to record commission in Finances.', [
                'tenant_id' => $tenantId,
                'amount' => $amount,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Re-throw the exception to be handled by the global exception handler
            // or a higher-level service, which might enqueue a job for a later retry.
            throw $e;
        }
    }
}
