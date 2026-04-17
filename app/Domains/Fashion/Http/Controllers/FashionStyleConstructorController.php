<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Controllers;

use App\Domains\Fashion\DTOs\FashionStyleAnalysisDto;
use App\Domains\Fashion\DTOs\FashionVirtualTryOnDto;
use App\Domains\Fashion\DTOs\FashionDynamicPricingDto;
use App\Domains\Fashion\DTOs\FashionWebRTCSessionDto;
use App\Domains\Fashion\DTOs\FashionLoyaltyDto;
use App\Domains\Fashion\DTOs\FashionARPreviewDto;
use App\Domains\Fashion\Services\AI\FashionStyleConstructorService;
use App\Domains\Fashion\Http\Requests\AnalyzeStyleRequest;
use App\Domains\Fashion\Http\Requests\VirtualTryOnRequest;
use App\Domains\Fashion\Http\Requests\DynamicPricingRequest;
use App\Domains\Fashion\Http\Requests\WebRTCSessionRequest;
use App\Domains\Fashion\Http\Requests\LoyaltyRewardRequest;
use App\Domains\Fashion\Http\Requests\SplitPaymentRequest;
use App\Domains\Fashion\Http\Requests\ARPreviewRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class FashionStyleConstructorController
{
    public function __construct(
        private FashionStyleConstructorService $styleConstructor,
    ) {}

    /**
     * Анализировать фото и сгенерировать персонализированный стиль.
     */
    public function analyzeAndRecommend(AnalyzeStyleRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;
        $isB2B = $request->boolean('is_b2b', false);

        if ($userId === 0) {
            throw ValidationException::withMessages([
                'user' => ['Authentication required'],
            ]);
        }

        $result = $this->styleConstructor->analyzeAndRecommend(
            photo: $request->file('photo'),
            userId: $userId,
            eventType: $request->input('event_type'),
            isB2B: $isB2B,
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result->toArray(),
            'correlation_id' => $correlationId,
        ], 201);
    }

    /**
     * Виртуальная примерка одежды с AR + embeddings.
     */
    public function virtualTryOn(VirtualTryOnRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;
        $isB2B = $request->boolean('is_b2b', false);

        if ($userId === 0) {
            throw ValidationException::withMessages([
                'user' => ['Authentication required'],
            ]);
        }

        $result = $this->styleConstructor->virtualTryOn(
            designId: $request->input('design_id'),
            userId: $userId,
            productIds: $request->input('product_ids'),
            isB2B: $isB2B,
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result->toArray(),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Динамическое ценообразование на основе AI-прогноза трендов.
     */
    public function applyDynamicPricing(DynamicPricingRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;
        $isB2B = $request->boolean('is_b2b', false);

        if ($userId === 0) {
            throw ValidationException::withMessages([
                'user' => ['Authentication required'],
            ]);
        }

        $result = $this->styleConstructor->applyDynamicPricing(
            productId: $request->input('product_id'),
            userId: $userId,
            isB2B: $isB2B,
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result->toArray(),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Инициировать WebRTC-сессию с персональным стилистом.
     */
    public function initiateWebRTCSession(WebRTCSessionRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;
        $isB2B = $request->boolean('is_b2b', false);

        if ($userId === 0) {
            throw ValidationException::withMessages([
                'user' => ['Authentication required'],
            ]);
        }

        $result = $this->styleConstructor->initiateWebRTCSession(
            userId: $userId,
            stylistId: $request->input('stylist_id'),
            scheduledTime: $request->input('scheduled_time'),
            isB2B: $isB2B,
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result->toArray(),
            'correlation_id' => $correlationId,
        ], 201);
    }

    /**
     * Получить AR-превью для примерки.
     */
    public function getARPreview(ARPreviewRequest $request, int $designId, int $productId): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            throw ValidationException::withMessages([
                'user' => ['Authentication required'],
            ]);
        }

        $result = $this->styleConstructor->getARPreview(
            designId: $designId,
            productId: $productId,
            userId: $userId,
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result->toArray(),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Начислить loyalty points и проверить NFT-аватар.
     */
    public function processLoyaltyReward(LoyaltyRewardRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            throw ValidationException::withMessages([
                'user' => ['Authentication required'],
            ]);
        }

        $result = $this->styleConstructor->processLoyaltyReward(
            userId: $userId,
            orderId: $request->input('order_id'),
            orderAmount: $request->input('order_amount'),
            rewardType: $request->input('reward_type', 'purchase'),
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result->toArray(),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Split payment: клиент + бренд + маркетплейс.
     */
    public function processSplitPayment(SplitPaymentRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;
        $isB2B = $request->boolean('is_b2b', false);

        if ($userId === 0) {
            throw ValidationException::withMessages([
                'user' => ['Authentication required'],
            ]);
        }

        $result = $this->styleConstructor->processSplitPayment(
            userId: $userId,
            orderId: $request->input('order_id'),
            totalAmount: $request->input('total_amount'),
            splitConfig: $request->input('split_config'),
            isB2B: $isB2B,
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Получить историю WebRTC-сессий пользователя.
     */
    public function getWebRTCSessionHistory(Request $request): JsonResponse
    {
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            throw ValidationException::withMessages([
                'user' => ['Authentication required'],
            ]);
        }

        $sessions = $this->styleConstructor->getUserWebRTCSessions($userId);

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * Получить loyalty баланс пользователя.
     */
    public function getLoyaltyBalance(Request $request): JsonResponse
    {
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            throw ValidationException::withMessages([
                'user' => ['Authentication required'],
            ]);
        }

        $balance = $this->styleConstructor->getUserLoyaltyBalance($userId);

        return response()->json([
            'success' => true,
            'data' => $balance,
        ]);
    }

    /**
     * Получить NFT аватары пользователя.
     */
    public function getUserNFTAvatars(Request $request): JsonResponse
    {
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            throw ValidationException::withMessages([
                'user' => ['Authentication required'],
            ]);
        }

        $avatars = $this->styleConstructor->getUserNFTAvatars($userId);

        return response()->json([
            'success' => true,
            'data' => $avatars,
        ]);
    }
}
