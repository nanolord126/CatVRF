<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Symfony\Component\HttpFoundation\Response;

final readonly class OctaneGracefulShutdown
{
    public function __construct(
        private readonly LogManager $log,
    ) {}
    public function handle(Request $request, Closure $next): Response
    {
        // Check if server is shutting down
        if ($this->isServerShuttingDown()) {
            $this->log->warning('Request received during shutdown', [
                'path' => $request->path(),
                'method' => $request->method(),
            ]);

            return response()->json([
                'error' => 'Service temporarily unavailable - server is restarting',
                'retry_after' => 30,
            ], 503);
        }

        return $next($request);
    }

    private function isServerShuttingDown(): bool
    {
        // Check Swoole server status if available
        if (function_exists('swoole_server')) {
            $server = \Laravel\Octane\Facades\Octane::server();
            if ($server && method_exists($server, 'isShutdown')) {
                return $server->isShutdown();
            }
        }

        // Fallback: check for maintenance mode
        return app()->isDownForMaintenance();
    }
}
