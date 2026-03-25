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

        $correlationId = $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString();

        // Run fraud check via FraudControlService::check()
        $fraudResult = $this->fraudControlService->check(
            (int) auth()->id(),
            'http_request',
            (int) $request->input('amount', 0),
            $request->ip(),
            $request->header('X-Device-Fingerprint'),
            $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            \Illuminate\Support\Facades\$this->log->channel('fraud_alert')->warning('High fraud score detected', [
                'user_id'        => auth()->id(),
                'score'          => $fraudResult['score'],
                'endpoint'       => $request->path(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'error'          => 'Operation blocked: suspicious activity detected',
                'score'          => $fraudResult['score'],
                'correlation_id' => $correlationId,
            ], 403);
        }

        // Store fraud result in request for later use
        $request->attributes->set('fraud_score', $fraudResult['score']);
        $request->attributes->set('correlation_id', $correlationId);

        return $next($request);
    }
}
