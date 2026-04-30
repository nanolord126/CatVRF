<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Warehouse\Domain\Services\ConsentManagementService;
use Modules\Warehouse\Domain\Exceptions\ConsentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Data Processing Consent Middleware
 * 
 * Middleware to verify user has consent for data processing per 152-ФЗ
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class CheckDataProcessingConsent
{
    public function __construct(
        private readonly ConsentManagementService $consentService
    ) {}

    public function handle(Request $request, Closure $next, string $consentType = 'personal_data_processing'): Response
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        try {
            $hasConsent = $this->consentService->hasActiveConsent($userId, $consentType);

            if (!$hasConsent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data processing consent required',
                    'consent_type' => $consentType,
                    'description' => ConsentManagementService::getConsentTypeDescription($consentType),
                ], 403);
            }

            return $next($request);
        } catch (ConsentException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid consent type',
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify consent',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
