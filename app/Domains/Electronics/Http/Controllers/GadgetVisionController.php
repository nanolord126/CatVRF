<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Http\Controllers;

use App\Domains\Electronics\DTOs\AI\GadgetVisionAnalysisRequestDto;
use App\Domains\Electronics\Services\AI\GadgetVisionRecommendationService;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Domains\Electronics\Models\ElectronicsProduct;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

final readonly class GadgetVisionController
{
    public function __construct(
        private readonly GadgetVisionRecommendationService $visionService,
        private readonly CacheManager $cache,
        private readonly Guard $guard,
    ) {}

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

        $userId = (int) $this->guard->id();
        $correlationId = (string) Str::uuid();
        $idempotencyKey = $request->input('idempotency_key');

        if ($idempotencyKey) {
            $cachedResponse = $this->getIdempotencyCache($idempotencyKey);
            if ($cachedResponse !== null) {
                return new JsonResponse($cachedResponse);
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

        return new JsonResponse($response->toArray());
    }

    public function getARModel(Request $request, int $productId): JsonResponse
    {
        $product = ElectronicsProduct::findOrFail($productId);

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

        return new JsonResponse($arData);
    }

    public function generateARQR(Request $request, int $productId): JsonResponse
    {
        $product = ElectronicsProduct::findOrFail($productId);

        $arUrl = url("/api/v1/electronics/products/{$productId}/ar-model");
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data='.urlencode($arUrl);

        return new JsonResponse([
            'product_id' => $productId,
            'ar_url' => $arUrl,
            'qr_code_url' => $qrCodeUrl,
            'download_url' => $qrCodeUrl.'&download=1',
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
        $cachedToken = $this->cache->get("video_call_token:{$token}");

        if (! $cachedToken || $cachedToken['user_id'] !== (int) $this->guard->id()) {
            return new JsonResponse(['error' => 'Invalid or expired token'], 401);
        }

        if (CarbonImmutable::now()->gt(Carbon::parse($cachedToken['expires_at']))) {
            return new JsonResponse(['error' => 'Token expired'], 401);
        }

        $roomName = 'electronics_expert_'.$token;
        $participantToken = hash('sha256', $this->guard->id().$roomName.CarbonImmutable::now()->timestamp);

        $this->cache->put(
            "video_call_room:{$roomName}",
            [
                'user_id' => (int) $this->guard->id(),
                'product_ids' => $request->input('product_ids'),
                'participant_token' => $participantToken,
                'created_at' => CarbonImmutable::now(),
            ],
            CarbonImmutable::now()->addMinutes(45)
        );

        return new JsonResponse([
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
        return $this->cache->get("idempotency:electronics_vision:{$key}");
    }

    private function setIdempotencyCache(string $key, array $data): void
    {
        $this->cache->put(
            "idempotency:electronics_vision:{$key}",
            $data,
            CarbonImmutable::now()->addHours(24)
        );
    }
}
