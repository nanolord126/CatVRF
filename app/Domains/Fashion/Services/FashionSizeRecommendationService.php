<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Size Recommendation Service для Fashion.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 * 
 * Рекомендация размера на основе антропометрических данных,
        истории покупок, бренда и типа товара.
 */
final readonly class FashionSizeRecommendationService
{
    private const CONFIDENCE_THRESHOLD = 0.7;

    public function __construct(
        private AuditService $audit,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
    ) {}

    /**
     * Рекомендовать размер для товара.
     */
    public function recommendSize(
        int $userId,
        int $productId,
        ?array $userMeasurements = null,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $this->fraud->check(
            userId: $userId,
            operationType: 'fashion_size_recommendation',
            amount: 0,
            correlationId: $correlationId
        );

        $product = $this->db->table('fashion_products')
            ->where('id', $productId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($product === null) {
            throw new \InvalidArgumentException('Product not found', 404);
        }

        $userProfile = $this->getUserSizeProfile($userId, $tenantId);
        $brandFitProfile = $this->getBrandFitProfile($product['brand'], $tenantId);
        $productCategory = $this->getProductCategory($productId, $tenantId);

        $recommendation = $this->calculateRecommendedSize(
            $userProfile,
            $brandFitProfile,
            $productCategory,
            $userMeasurements,
            $correlationId
        );

        $this->recordSizeRecommendation($userId, $tenantId, $productId, $recommendation, $correlationId);

        $this->audit->record(
            action: 'fashion_size_recommended',
            subjectType: 'fashion_size_recommendation',
            subjectId: $userId,
            oldValues: [],
            newValues: [
                'product_id' => $productId,
                'recommended_size' => $recommendation['size'],
                'confidence' => $recommendation['confidence'],
            ],
            correlationId: $correlationId
        );

        Log::channel('audit')->info('Fashion size recommended', [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'recommended_size' => $recommendation['size'],
            'correlation_id' => $correlationId,
        ]);

        return [
            'user_id' => $userId,
            'product_id' => $productId,
            'recommended_size' => $recommendation['size'],
            'confidence' => $recommendation['confidence'],
            'reason' => $recommendation['reason'],
            'alternative_sizes' => $recommendation['alternative_sizes'],
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Обновить профиль размеров пользователя.
     */
    public function updateUserSizeProfile(
        int $userId,
        array $measurements,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $this->db->table('fashion_user_size_profiles')->updateOrInsert(
            ['user_id' => $userId, 'tenant_id' => $tenantId],
            [
                'height' => $measurements['height'] ?? null,
                'weight' => $measurements['weight'] ?? null,
                'chest' => $measurements['chest'] ?? null,
                'waist' => $measurements['waist'] ?? null,
                'hips' => $measurements['hips'] ?? null,
                'shoe_size' => $measurements['shoe_size'] ?? null,
                'updated_at' => Carbon::now(),
                'correlation_id' => $correlationId,
            ]
        );

        $this->audit->record(
            action: 'fashion_user_size_profile_updated',
            subjectType: 'fashion_size_profile',
            subjectId: $userId,
            oldValues: [],
            newValues: $measurements,
            correlationId: $correlationId
        );

        return [
            'user_id' => $userId,
            'profile_updated' => true,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить историю размеров пользователя.
     */
    public function getUserSizeHistory(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $purchases = $this->db->table('order_items as oi')
            ->join('orders as o', 'oi.order_id', '=', 'o.id')
            ->join('fashion_sizes as fs', 'oi.fashion_size_id', '=', 'fs.id')
            ->where('o.user_id', $userId)
            ->where('o.tenant_id', $tenantId)
            ->where('o.status', 'completed')
            ->select('oi.product_id', 'fs.size_value', 'o.created_at')
            ->orderBy('o.created_at', 'desc')
            ->limit(50)
            ->get()
            ->toArray();

        $sizePreferences = [];
        foreach ($purchases as $purchase) {
            $size = $purchase['size_value'];
            $sizePreferences[$size] = ($sizePreferences[$size] ?? 0) + 1;
        }

        arsort($sizePreferences);

        return [
            'user_id' => $userId,
            'total_purchases' => count($purchases),
            'size_preferences' => $sizePreferences,
            'recent_purchases' => array_slice($purchases, 0, 10, true),
            'correlation_id' => $correlationId,
        ];
    }

    private function getUserSizeProfile(int $userId, int $tenantId): array
    {
        $profile = $this->db->table('fashion_user_size_profiles')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();

        return $profile !== null ? [
            'height' => $profile['height'],
            'weight' => $profile['weight'],
            'chest' => $profile['chest'],
            'waist' => $profile['waist'],
            'hips' => $profile['hips'],
            'shoe_size' => $profile['shoe_size'],
        ] : [];
    }

    private function getBrandFitProfile(string $brand, int $tenantId): array
    {
        $profile = $this->db->table('fashion_brand_fit_profiles')
            ->where('brand', $brand)
            ->where('tenant_id', $tenantId)
            ->first();

        return $profile !== null ? [
            'runs_small' => $profile['runs_small'],
            'runs_large' => $profile['runs_large'],
            'true_to_size' => $profile['true_to_size'],
        ] : [
            'runs_small' => 0,
            'runs_large' => 0,
            'true_to_size' => 1.0,
        ];
    }

    private function getProductCategory(int $productId, int $tenantId): string
    {
        $category = $this->db->table('fashion_product_categories')
            ->where('product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->first();

        return $category !== null ? $category['primary_category'] : 'other';
    }

    private function calculateRecommendedSize(
        array $userProfile,
        array $brandFitProfile,
        string $productCategory,
        ?array $userMeasurements,
        string $correlationId
    ): array {
        $baseSize = $this->determineBaseSize($userProfile, $productCategory, $userMeasurements);
        $adjustedSize = $this->applyBrandAdjustment($baseSize, $brandFitProfile);
        $confidence = $this->calculateConfidence($userProfile, $brandFitProfile);

        return [
            'size' => $adjustedSize,
            'confidence' => $confidence,
            'reason' => $this->getRecommendationReason($userProfile, $brandFitProfile),
            'alternative_sizes' => $this->getAlternativeSizes($adjustedSize),
        ];
    }

    private function determineBaseSize(array $userProfile, string $productCategory, ?array $userMeasurements): string
    {
        if (!empty($userMeasurements)) {
            return $this->calculateSizeFromMeasurements($userMeasurements, $productCategory);
        }

        if (!empty($userProfile)) {
            return $this->calculateSizeFromProfile($userProfile, $productCategory);
        }

        return 'M';
    }

    private function calculateSizeFromMeasurements(array $measurements, string $productCategory): string
    {
        $chest = $measurements['chest'] ?? 0;
        $waist = $measurements['waist'] ?? 0;

        return match ($productCategory) {
            'tops' => match (true) {
                $chest < 90 => 'XS',
                $chest < 96 => 'S',
                $chest < 102 => 'M',
                $chest < 108 => 'L',
                $chest < 114 => 'XL',
                default => 'XXL',
            },
            'bottoms' => match (true) {
                $waist < 70 => 'XS',
                $waist < 76 => 'S',
                $waist < 82 => 'M',
                $waist < 88 => 'L',
                $waist < 94 => 'XL',
                default => 'XXL',
            },
            'shoes' => $measurements['shoe_size'] ?? '40',
            default => 'M',
        };
    }

    private function calculateSizeFromProfile(array $profile, string $productCategory): string
    {
        $height = $profile['height'] ?? 170;
        $weight = $profile['weight'] ?? 70;

        $bmi = $weight / (($height / 100) ** 2);

        return match (true) {
            $bmi < 18.5 => 'S',
            $bmi < 25 => 'M',
            $bmi < 30 => 'L',
            default => 'XL',
        };
    }

    private function applyBrandAdjustment(string $baseSize, array $brandFitProfile): string
    {
        $sizeOrder = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $currentIndex = array_search($baseSize, $sizeOrder);

        if ($currentIndex === false) {
            return $baseSize;
        }

        if ($brandFitProfile['runs_large'] > 0.5) {
            $newIndex = max(0, $currentIndex - 1);
            return $sizeOrder[$newIndex];
        }

        if ($brandFitProfile['runs_small'] > 0.5) {
            $newIndex = min(count($sizeOrder) - 1, $currentIndex + 1);
            return $sizeOrder[$newIndex];
        }

        return $baseSize;
    }

    private function calculateConfidence(array $userProfile, array $brandFitProfile): float
    {
        $confidence = 0.5;
        
        if (!empty($userProfile)) {
            $confidence += 0.3;
        }

        if ($brandFitProfile['true_to_size'] > 0.8) {
            $confidence += 0.2;
        }

        return min($confidence, 1.0);
    }

    private function getRecommendationReason(array $userProfile, array $brandFitProfile): string
    {
        $reasons = [];

        if (!empty($userProfile)) {
            $reasons[] = 'Based on your measurements';
        }

        if ($brandFitProfile['runs_large'] > 0.5) {
            $reasons[] = 'Brand runs large, recommended smaller size';
        } elseif ($brandFitProfile['runs_small'] > 0.5) {
            $reasons[] = 'Brand runs small, recommended larger size';
        }

        return implode('. ', $reasons) ?: 'Based on general sizing';
    }

    private function getAlternativeSizes(string $size): array
    {
        $sizeOrder = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $currentIndex = array_search($size, $sizeOrder);

        if ($currentIndex === false) {
            return [];
        }

        $alternatives = [];
        if ($currentIndex > 0) {
            $alternatives[] = $sizeOrder[$currentIndex - 1];
        }
        if ($currentIndex < count($sizeOrder) - 1) {
            $alternatives[] = $sizeOrder[$currentIndex + 1];
        }

        return $alternatives;
    }

    private function recordSizeRecommendation(int $userId, int $tenantId, int $productId, array $recommendation, string $correlationId): void
    {
        $this->db->table('fashion_size_recommendations')->insert([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'recommended_size' => $recommendation['size'],
            'confidence' => $recommendation['confidence'],
            'recommended_at' => Carbon::now(),
            'correlation_id' => $correlationId,
        ]);
    }

    private function getTenantId(): int
    {
        return function_exists('tenant') && tenant() ? tenant()->id : 1;
    }
}
