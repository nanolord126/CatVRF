<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Security\VoiceBiometricsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final class VoiceBiometricsController extends Controller
{
    public function __construct(
        private readonly VoiceBiometricsService $voiceBiometrics,
    ) {}

    /**
     * Enroll voice sample
     */
    public function enroll(Request $request): JsonResponse
    {
        $request->validate([
            'audio_data' => 'required|string',
        ]);

        $result = $this->voiceBiometrics->enrollVoice(
            userId: (int) auth()->id(),
            audioData: $request->input('audio_data'),
            correlationId: $request->header('X-Correlation-ID') ?? '',
        );

        return new JsonResponse($result);
    }

    /**
     * Verify voice
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'audio_data' => 'required|string',
        ]);

        $result = $this->voiceBiometrics->verifyVoice(
            userId: (int) auth()->id(),
            audioData: $request->input('audio_data'),
            correlationId: $request->header('X-Correlation-ID') ?? '',
        );

        return new JsonResponse($result);
    }

    /**
     * Get voice profile status
     */
    public function status(Request $request): JsonResponse
    {
        $status = $this->voiceBiometrics->getProfileStatus(
            userId: (int) auth()->id(),
        );

        return new JsonResponse($status);
    }

    /**
     * Delete voice profile
     */
    public function delete(Request $request): JsonResponse
    {
        $this->voiceBiometrics->deleteVoiceProfile(
            userId: (int) auth()->id(),
            correlationId: $request->header('X-Correlation-ID') ?? '',
        );

        return new JsonResponse([
            'success' => true,
            'message' => 'Voice profile deleted successfully',
        ]);
    }
}
