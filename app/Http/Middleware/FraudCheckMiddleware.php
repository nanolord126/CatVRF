<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\FraudControlService;
use Closure;
use Illuminate\Http\Request;

final class FraudCheckMiddleware
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {
    }

    /**
     * Check fraud on critical endpoints (payments, withdrawals, promos, wishlist)
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (!auth()->check()) {
            return response()->json([
                'error' => 'Unauthorized',
                'correlation_id' => $request->header('X-Correlation-ID'),
            ], 401);
        }

        $correlationId = $request->header('X-Correlation-ID') ?? (string)\Illuminate\Support\Str::uuid();

        // Prepare fraud check data
        $operationData = [
            'user_id' => auth()->id(),
            'ip_address' => $request->ip() ?? '0.0.0.0',
            'user_agent' => $request->header('User-Agent'),
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'amount' => $request->input('amount'),
            'correlation_id' => $correlationId,
        ];

        // Run fraud check
        $fraudScore = $this->fraudControlService->scoreOperation($operationData);

        if ($fraudScore >= 0.8) {
            \Illuminate\Support\Facades\Log::channel('fraud_alert')->warning('High fraud score detected', [
                'user_id' => auth()->id(),
                'score' => $fraudScore,
                'endpoint' => $request->path(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'error' => 'Operation blocked: suspicious activity detected',
                'score' => $fraudScore,
                'correlation_id' => $correlationId,
            ], 403);
        }

        // Store fraud score in request for later use
        $request->attributes->set('fraud_score', $fraudScore);
        $request->attributes->set('correlation_id', $correlationId);

        return $next($request);
    }
}
