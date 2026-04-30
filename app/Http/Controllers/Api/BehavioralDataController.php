<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Support\Collection;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Behavioral\BehavioralBiometricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;

/**
 * Behavioral Data Controller
 *
 * Receives behavioral biometrics data from frontend SDK
 * for continuous authentication analysis
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class BehavioralDataController extends Controller
{
    public function __construct(
        private readonly BehavioralBiometricsService $behavioralBiometrics,
    ) {}

    /**
     * Collect behavioral data from frontend
     */
    public function new Collection(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->auth->guard('api')->user();

        if (! $user) {
            return new JsonResponse([
                'error' => 'Unauthenticated',
            ], 401);
        }

        $signals = $request->only(['typing', 'mouse', 'touch']);
        $sessionId = $request->input('sessionId', session()->getId());

        try {
            $result = $this->behavioralBiometrics->analyzeSignals(
                $user,
                $signals,
                $sessionId
            );

            return new JsonResponse([
                'success' => true,
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's behavioral baseline
     */
    public function getBaseline(): JsonResponse
    {
        /** @var User $user */
        $user = $this->auth->guard('api')->user();

        if (! $user) {
            return new JsonResponse([
                'error' => 'Unauthenticated',
            ], 401);
        }

        $baseline = $this->behavioralBiometrics->getBaseline($user);

        return new JsonResponse([
            'success' => true,
            'baseline' => $baseline,
        ]);
    }

    /**
     * Build behavioral baseline (enrollment)
     */
    public function buildBaseline(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->auth->guard('api')->user();

        if (! $user) {
            return new JsonResponse([
                'error' => 'Unauthenticated',
            ], 401);
        }

        $samples = $request->input('samples', []);

        $result = $this->behavioralBiometrics->buildBaseline($user, $samples);

        return new JsonResponse([
            'success' => $result,
        ]);
    }
}
