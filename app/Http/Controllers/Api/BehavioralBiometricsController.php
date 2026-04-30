<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\BehavioralAnomalyDetectionJob;
use App\Models\BehavioralDataPoint;
use App\Models\User;
use App\Services\Behavioral\BehavioralBiometricsService;
use App\Services\Security\BehavioralBiometricsCollectorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Behavioral Biometrics API Controller
 *
 * Provides REST API endpoints for behavioral biometrics:
 * - Collect signals from frontend
 * - Get baseline status
 * - Reset profile (for privacy/right to deletion)
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final class BehavioralBiometricsController extends Controller
{
    public function __construct(
        private readonly BehavioralBiometricsCollectorService $collector,
        private readonly BehavioralBiometricsService $service,
    ) {}

    /**
     * Collect behavioral signals from frontend
     *
     * POST /api/behavioral/collect
     *
     * Request body:
     * {
     *   "action": "login|registration|client_search|...",
     *   "signals": {
     *     "typing": { "events": [...] },
     *     "mouse": { "events": [...] },
     *     "touch": { "events": [...] }
     *   }
     * }
     */
    public function collect(Request $request): JsonResponse
    {
        if (! config('behavioral.enabled', true)) {
            return response()->json([
                'success' => false,
                'error' => 'Behavioral biometrics disabled',
            ], 503);
        }

        $result = $this->collector->collect($request);

        if (! $result['success']) {
            return response()->json($result, 400);
        }

        // Dispatch async anomaly detection job if enabled
        if (config('behavioral.performance.async_analysis', true)) {
            $dataPoint = BehavioralDataPoint::where('session_id', session()->getId())
                ->where('user_id', Auth::id())
                ->orderBy('id', 'desc')
                ->first();

            if ($dataPoint) {
                BehavioralAnomalyDetectionJob::dispatch(
                    dataPointId: $dataPoint->id,
                    userId: Auth::id(),
                    sessionId: session()->getId(),
                );
            }
        }

        return response()->json($result);
    }

    /**
     * Get user's baseline status
     *
     * GET /api/behavioral/baseline
     */
    public function getBaseline(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $baseline = $this->service->getBaseline($user);

        return response()->json([
            'baseline' => $baseline,
            'has_baseline' => ! empty($baseline),
            'profile_stats' => $this->service->getProfileStats($user),
        ]);
    }

    /**
     * Get current session score
     *
     * GET /api/behavioral/session-score
     */
    public function getSessionScore(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $sessionId = session()->getId();
        $score = $this->service->getSessionScore($user->id, $sessionId);

        return response()->json([
            'session_id' => $sessionId,
            'score' => $score,
            'has_score' => $score !== null,
        ]);
    }

    /**
     * Reset behavioral profile (privacy/right to deletion)
     *
     * DELETE /api/behavioral/profile
     */
    public function resetProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $this->securityService->resetProfile($user);

        Log::info('Behavioral profile reset by user', [
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile reset successfully',
        ]);
    }

    /**
     * Update consent for behavioral data collection
     *
     * PUT /api/behavioral/consent
     */
    public function updateConsent(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'consent' => 'required|boolean',
        ]);

        $user->behavioral_consent = $request->boolean('consent');
        $user->save();

        Log::info('Behavioral consent updated', [
            'user_id' => $user->id,
            'consent' => $user->behavioral_consent,
        ]);

        return response()->json([
            'success' => true,
            'consent' => $user->behavioral_consent,
        ]);
    }

    /**
     * Get consent status
     *
     * GET /api/behavioral/consent
     */
    public function getConsent(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'consent' => (bool) ($user->behavioral_consent ?? config('behavioral.consent.default_enabled', true)),
            'version' => config('behavioral.consent.consent_version', '1.0'),
        ]);
    }
}
