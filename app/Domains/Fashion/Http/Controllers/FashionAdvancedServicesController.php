<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Controllers;

use App\Domains\Fashion\Services\FashionCollaborativeFilteringService;
use App\Domains\Fashion\Services\FashionSocialMediaTrendService;
use App\Domains\Fashion\Services\FashionReviewModerationService;
use App\Domains\Fashion\Services\FashionVisualSearchService;
use App\Domains\Fashion\Services\FashionSizeRecommendationService;
use App\Domains\Fashion\Services\FashionInventoryForecastingService;
use App\Domains\Fashion\Services\FashionABPriceTestingService;
use App\Domains\Fashion\Services\FashionEmailCampaignService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class FashionAdvancedServicesController
{
    public function __construct(
        private FashionCollaborativeFilteringService $collaborative,
        private FashionSocialMediaTrendService $trends,
        private FashionReviewModerationService $moderation,
        private FashionVisualSearchService $visualSearch,
        private FashionSizeRecommendationService $sizeRec,
        private FashionInventoryForecastingService $inventory,
        private FashionABPriceTestingService $abPrice,
        private FashionEmailCampaignService $emailCampaign,
    ) {}

    // Collaborative Filtering
    public function getRecommendations(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            throw ValidationException::withMessages(['user' => ['Authentication required']]);
        }

        $validated = $request->validate([
            'algorithm' => ['nullable', 'in:user-based,item-based,matrix-factorization,hybrid'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $result = $this->collaborative->getRecommendations(
            userId: $userId,
            algorithm: $validated['algorithm'] ?? 'hybrid',
            limit: (int) ($validated['limit'] ?? 20),
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    // Social Media Trends
    public function collectTrends(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $result = $this->trends->collectTrendData($correlationId);

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    public function analyzeProductTrends(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:fashion_products,id'],
        ]);

        $result = $this->trends->analyzeProductTrends(
            productId: $validated['product_id'],
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    // Review Moderation
    public function moderateReview(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        $validated = $request->validate([
            'review_id' => ['required', 'integer', 'exists:fashion_reviews,id'],
        ]);

        $result = $this->moderation->moderateReview(
            reviewId: $validated['review_id'],
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    public function getFlaggedReviews(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->moderation->getFlaggedReviews(
            limit: (int) ($validated['limit'] ?? 50),
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    // Visual Search
    public function searchByImage(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            throw ValidationException::withMessages(['user' => ['Authentication required']]);
        }

        $validated = $request->validate([
            'image_url' => ['required', 'url'],
            'filters' => ['nullable', 'array'],
        ]);

        $result = $this->visualSearch->searchByImage(
            imageUrl: $validated['image_url'],
            userId: $userId,
            filters: $validated['filters'] ?? [],
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    public function indexProductForSearch(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:fashion_products,id'],
        ]);

        $result = $this->visualSearch->indexProductForVisualSearch(
            productId: $validated['product_id'],
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    // Size Recommendation
    public function recommendSize(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            throw ValidationException::withMessages(['user' => ['Authentication required']]);
        }

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:fashion_products,id'],
            'measurements' => ['nullable', 'array'],
        ]);

        $result = $this->sizeRec->recommendSize(
            userId: $userId,
            productId: $validated['product_id'],
            userMeasurements: $validated['measurements'] ?? null,
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    public function updateSizeProfile(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            throw ValidationException::withMessages(['user' => ['Authentication required']]);
        }

        $validated = $request->validate([
            'measurements' => ['required', 'array'],
        ]);

        $result = $this->sizeRec->updateUserSizeProfile(
            userId: $userId,
            measurements: $validated['measurements'],
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    // Inventory Forecasting
    public function forecastDemand(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:fashion_products,id'],
            'days_ahead' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        $result = $this->inventory->forecastDemand(
            productId: $validated['product_id'],
            daysAhead: (int) ($validated['days_ahead'] ?? 30),
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    public function getReorderRecommendations(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $result = $this->inventory->getReorderRecommendations($correlationId);

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    public function getOutOfStockStats(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $result = $this->inventory->getOutOfStockStats(
            days: (int) ($validated['days'] ?? 30),
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    // A/B Price Testing
    public function createPriceTest(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:fashion_products,id'],
            'control_price' => ['required', 'numeric', 'min:0'],
            'test_price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $result = $this->abPrice->createPriceTest(
            productId: $validated['product_id'],
            controlPrice: (float) $validated['control_price'],
            testPrice: (float) $validated['test_price'],
            durationDays: (int) ($validated['duration_days'] ?? 14),
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    public function getPriceTestResults(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        $validated = $request->validate([
            'test_id' => ['required', 'integer', 'exists:fashion_ab_price_tests,id'],
        ]);

        $result = $this->abPrice->getTestResults(
            testId: $validated['test_id'],
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    public function stopPriceTest(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        $validated = $request->validate([
            'test_id' => ['required', 'integer', 'exists:fashion_ab_price_tests,id'],
        ]);

        $result = $this->abPrice->stopTest(
            testId: $validated['test_id'],
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    // Email Campaigns
    public function createCampaign(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'template' => ['required', 'string'],
            'segmentation_rules' => ['required', 'array'],
            'scheduled_for' => ['nullable', 'date'],
        ]);

        $result = $this->emailCampaign->createCampaign(
            name: $validated['name'],
            subject: $validated['subject'],
            template: $validated['template'],
            segmentationRules: $validated['segmentation_rules'],
            scheduledFor: $validated['scheduled_for'] ? Carbon::parse($validated['scheduled_for']) : null,
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    public function sendCampaign(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        $validated = $request->validate([
            'campaign_id' => ['required', 'integer', 'exists:fashion_email_campaigns,id'],
        ]);

        $result = $this->emailCampaign->sendCampaign(
            campaignId: $validated['campaign_id'],
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    public function getCampaignStats(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        $validated = $request->validate([
            'campaign_id' => ['required', 'integer', 'exists:fashion_email_campaigns,id'],
        ]);

        $result = $this->emailCampaign->getCampaignStats(
            campaignId: $validated['campaign_id'],
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }
}
