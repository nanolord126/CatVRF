<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Monitoring\FilamentMetricsService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Filament Metrics Middleware
 * 
 * Collects Prometheus metrics for all Filament requests:
 * - Request duration
 * - Resource views
 * - Error tracking
 * - Performance monitoring
 * 
 * @package App\Http\Middleware
 */
final readonly class FilamentMetricsMiddleware
{
    private readonly FilamentMetricsService $metrics;

    public function __construct(FilamentMetricsService $metrics)
    {
        $this->metrics = $metrics;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to Filament requests
        if (!$this->isFilamentRequest($request)) {
            return $next($request);
        }

        $startTime = microtime(true);
        $panel = $this->detectPanel($request);

        try {
            $response = $next($request);

            // Record metrics
            $duration = microtime(true) - $startTime;
            $this->recordMetrics($request, $response, $duration, $panel, 'success');

            return $response;

        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;
            
            // Record error metrics
            $this->recordMetrics($request, null, $duration, $panel, 'error');
            $this->metrics->incrementError(get_class($e), $panel);

            throw $e;
        }
    }

    /**
     * Record metrics for the request
     */
    private function recordMetrics(Request $request, ?Response $response, float $duration, string $panel, string $status): void
    {
        $path = $request->path();
        
        // Record action execution time
        $action = $this->extractActionName($path);
        if ($action) {
            $this->metrics->recordActionExecution($action, $duration, $status, $panel);
        }

        // Record resource views
        if ($this->isResourceView($path)) {
            $resource = $this->extractResourceName($path);
            if ($resource && $status === 'success') {
                $this->metrics->incrementResourceView($resource, $panel);
            }
        }
    }

    /**
     * Check if request is for Filament
     */
    private function isFilamentRequest(Request $request): bool
    {
        $path = $request->path();
        
        return str_starts_with($path, 'admin') 
            || str_starts_with($path, 'tenant') 
            || str_starts_with($path, 'dashboard')
            || str_starts_with($path, 'b2b');
    }

    /**
     * Detect which panel is being accessed
     */
    private function detectPanel(Request $request): string
    {
        $path = $request->path();

        return match (true) {
            str_starts_with($path, 'admin') => 'landlord',
            str_starts_with($path, 'b2b') => 'b2b',
            default => 'tenant',
        };
    }

    /**
     * Extract action name from path
     */
    private function extractActionName(string $path): ?string
    {
        if (preg_match('/\/([a-z-]+)\/\d+/', $path, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Check if request is for a resource view
     */
    private function isResourceView(string $path): bool
    {
        return str_contains($path, '/resources/') 
            || preg_match('/\/[a-z-]+\/\d+/', $path);
    }

    /**
     * Extract resource name from path
     */
    private function extractResourceName(string $path): ?string
    {
        if (preg_match('/\/resources\/([a-z-]+)/', $path, $matches)) {
            return $matches[1];
        }

        if (preg_match('/\/([a-z-]+)\/\d+/', $path, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
