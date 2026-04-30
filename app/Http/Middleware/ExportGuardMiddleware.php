<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Services\Security\ClientDataProtectionService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Export Guard Middleware
 * 
 * Blocks bulk data exports and enforces export limits.
 * Prevents mass data extraction from client databases.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Data Exfiltration Fortress.
 */
final readonly class ExportGuardMiddleware
{
    public function __construct(
        private readonly ClientDataProtectionService $clientDataProtection,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for non-export routes
        if (! $this->isExportRequest($request)) {
            return $next($request);
        }

        $user = Auth::user();
        if (! $user) {
            return new JsonResponse([
                'error' => 'Unauthorized',
                'message' => 'Authentication required for data export',
            ], 401);
        }

        try {
            // Check export permissions
            $this->clientDataProtection->checkAccess($user, 'export');

            // Validate export limit
            $recordCount = $this->getRequestedRecordCount($request);
            if ($recordCount > 0) {
                $this->clientDataProtection->validateExport($user, $recordCount);
            }

            // Log export attempt
            $this->clientDataProtection->logDataAccess($user, 'export_attempt', [
                'route' => $request->route()?->getName(),
                'record_count' => $recordCount,
            ]);

            return $next($request);
        } catch (\RuntimeException $e) {
            return new JsonResponse([
                'error' => 'Export denied',
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Check if request is an export request
     */
    private function isExportRequest(Request $request): bool
    {
        $path = $request->path();

        // Check for export endpoints
        $exportPatterns = [
            '/export',
            '/download',
            '/bulk-export',
            '/api/v1/*/export',
            '/api/v1/*/download',
        ];

        foreach ($exportPatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                return true;
            }
        }

        // Check for export query parameters
        if ($request->has('export') || $request->has('download') || $request->has('format')) {
            return true;
        }

        // Check for Filament bulk export
        if ($request->is('filament/*') && $request->has('bulkExport')) {
            return true;
        }

        return false;
    }

    /**
     * Get requested record count from request
     */
    private function getRequestedRecordCount(Request $request): int
    {
        // Check query parameters
        if ($request->has('limit')) {
            return (int) $request->input('limit');
        }

        if ($request->has('count')) {
            return (int) $request->input('count');
        }

        // Check for bulk export parameter
        if ($request->has('bulkExport') || $request->has('all')) {
            return 10000; // Assume large export
        }

        return 0;
    }
}
