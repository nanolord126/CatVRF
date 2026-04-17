<?php declare(strict_types=1);

namespace App\Domains\Electronics\Http\Controllers;

use App\Domains\Electronics\DTOs\AI\GadgetVisionAnalysisRequestDto;
use App\Domains\Electronics\DTOs\AI\GadgetVisionAnalysisResponseDto;
use App\Domains\Electronics\Services\AI\GadgetVisionRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

final readonly class GadgetVisionController
{
    public function __construct(
        private GadgetVisionRecommendationService $visionService,
    ) {
    }

    public function analyze(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:10240',
            'budget_max_kopecks' => 'required|integer|min:0',
            'analysis_type' => 'required|string|in:gadget_recommendation,room_analysis',
            'preferred_brands' => 'array',
            'preferred_brands.*' => 'string',
            'use_cases' => 'array',
            'use_cases.*' => 'string',
            'additional_specs' => 'array',
            'idempotency_key' => 'nullable|string|max:255',
        ]);

        $userId = Auth::id();
        $correlationId = (string) Str::uuid();
        $idempotencyKey = $request->input('idempotency_key');

        if ($idempotencyKey) {
            $cachedResponse = $this->getIdempotencyCache($idempotencyKey);
            if ($cachedResponse !== null) {
                return response()->json($cachedResponse);
            }
        }

        $dto = GadgetVisionAnalysisRequestDto::fromRequest(
            $request->all(),
            $request->file('image'),
            $userId,
            $correlationId
        );

        $response = $this->visionService->analyzePhotoAndRecommend($dto);

        if ($idempotencyKey) {
            $this->setIdempotencyCache($idempotencyKey, $response->toArray());
        }

        return response()->json($response->toArray());
    }

    public function getARModel(Request $request, int $productId): JsonResponse
    {
        $product = \App\Domains\Electronics\Models\ElectronicsProduct::findOrFail($productId);

        $arData = [
            'product_id' => $productId,
            'model_url' => $product->ar_model_url ?? null,
            'fallback_image' => $product->images[0] ?? null,
            'ar_type' => 'webxr',
            'viewer_config' => [
                'auto_rotate' => true,
                'camera_controls' => true,
                'shadow_intensity' => 0.5,
            ],
        ];

        return response()->json($arData);
    }

    public function generateARQR(Request $request, int $productId): JsonResponse
    {
        $product = \App\Domains\Electronics\Models\ElectronicsProduct::findOrFail($productId);

        $arUrl = url("/api/v1/electronics/products/{$productId}/ar-model");
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($arUrl);

        return response()->json([
            'product_id' => $productId,
            'ar_url' => $arUrl,
            'qr_code_url' => $qrCodeUrl,
            'download_url' => $qrCodeUrl . '&download=1',
        ]);
    }

    public function initiateVideoCall(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer',
        ]);

        $token = $request->input('token');
        $cachedToken = \Illuminate\Support\Facades\Cache::get("video_call_token:{$token}");

        if (!$cachedToken || $cachedToken['user_id'] !== Auth::id()) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }

        if (now()->gt(\Carbon\Carbon::parse($cachedToken['expires_at']))) {
            return response()->json(['error' => 'Token expired'], 401);
        }

        $roomName = 'electronics_expert_' . $token;
        $participantToken = hash('sha256', Auth::id() . $roomName . now()->timestamp);

        \Illuminate\Support\Facades\Cache::put(
            "video_call_room:{$roomName}",
            [
                'user_id' => Auth::id(),
                'product_ids' => $request->input('product_ids'),
                'participant_token' => $participantToken,
                'created_at' => now(),
            ],
            now()->addMinutes(45)
        );

        return response()->json([
            'room_name' => $roomName,
            'participant_token' => $participantToken,
            'webrtc_config' => [
                'ice_servers' => config('services.webrtc.ice_servers', [
                    ['urls' => 'stun:stun.l.google.com:19302'],
                ]),
                'signaling_url' => config('services.webrtc.signaling_url'),
            ],
            'expires_in_minutes' => 45,
        ]);
    }

    private function getIdempotencyCache(string $key): ?array
    {
        return \Illuminate\Support\Facades\Cache::get("idempotency:electronics_vision:{$key}");
    }

    private function setIdempotencyCache(string $key, array $data): void
    {
        \Illuminate\Support\Facades\Cache::put(
            "idempotency:electronics_vision:{$key}",
            $data,
            now()->addHours(24)
        );
    }
}
