<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use Modules\Supermarket\Infrastructure\Models\ProductNutrition;
use Modules\CatCRM\Domain\Verticals\Supermarket\SupermarketOrder;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;

/**
 * HalalCertificationService — Сервис управления халяль сертификацией
 * 
 * Управляет халяль статусами товаров, проверкой совместимости,
 * верификацией сертификатов и фильтрацией для клиентов.
 */
final class HalalCertificationService
{
    use WithAuditLogging;
    use WithTelemetry;

    private readonly FraudControlService $fraudControl;

    public function __construct(FraudControlService $fraudControl)
    {
        $this->fraudControl = $fraudControl;
    }

    // ========================
    // CERTIFICATION MANAGEMENT
    // ========================

    /**
     * Установить халяль статус для продукта
     */
    public function setHalalStatus(
        ProductNutrition $nutrition,
        bool $isHalal,
        ?string $authority = null,
        ?string $certificateNumber = null,
        ?CarbonImmutable $validUntil = null
    ): bool {
        return $this->withSpan(
            'supermarket_halal.set_status',
            function () use ($nutrition, $isHalal, $authority, $certificateNumber, $validUntil) {
            $metadata = $nutrition->metadata ?? [];
            
            if ($isHalal) {
                $metadata['halal_authority'] = $authority;
                $metadata['halal_certificate_number'] = $certificateNumber;
                $metadata['halal_valid_until'] = $validUntil?->toIso8601String();
                $metadata['halal_verified_at'] = now()->toIso8601String();
            } else {
                unset($metadata['halal_authority']);
                unset($metadata['halal_certificate_number']);
                unset($metadata['halal_valid_until']);
                unset($metadata['halal_verified_at']);
            }

            $nutrition->update([
                'halal' => $isHalal,
                'metadata' => $metadata,
                'nutrition_verified_at' => now(),
            ]);

            $this->logAction('halal_status_updated', $nutrition->product_id, [
                'is_halal' => $isHalal,
                'authority' => $authority,
                'certificate_number' => $certificateNumber,
            ], null, $nutrition->tenant_id);

            Log::info('Halal status updated', [
                'product_id' => $nutrition->product_id,
                'is_halal' => $isHalal,
                'authority' => $authority,
            ]);

            return true;
        },
            $this->getStandardAttributes('supermarket', 'halal_set_status'),
        );
    }

    /**
     * Верифицировать халяль сертификат
     */
    public function verifyHalalCertificate(
        ProductNutrition $nutrition,
        string $certificateNumber,
        string $authority
    ): array {
        return $this->withSpan(
            'supermarket_halal.verify_certificate',
            function () use ($nutrition, $certificateNumber, $authority) {
                // TODO: Implement actual certificate verification with external API
                // For now, simulate verification
                
                $isValid = $this->validateCertificateFormat($certificateNumber);
                $validUntil = now()->addYears(1); // Default 1 year validity

                if ($isValid) {
                    $metadata = $nutrition->metadata ?? [];
                    $metadata['halal_authority'] = $authority;
                    $metadata['halal_certificate_number'] = $certificateNumber;
                    $metadata['halal_valid_until'] = $validUntil->toIso8601String();
                    $metadata['halal_verified_at'] = now()->toIso8601String();
                    $metadata['halal_verification_status'] = 'verified';

                    $nutrition->update([
                        'halal' => true,
                        'metadata' => $metadata,
                        'nutrition_verified_at' => now(),
                    ]);

                    $this->logAction('halal_certificate_verified', $nutrition->product_id, [
                        'certificate_number' => $certificateNumber,
                        'authority' => $authority,
                        'valid_until' => $validUntil->toIso8601String(),
                    ], null, $nutrition->tenant_id);
                }

                return [
                    'valid' => $isValid,
                    'certificate_number' => $certificateNumber,
                    'authority' => $authority,
                    'valid_until' => $validUntil->toIso8601String(),
                    'verified_at' => now()->toIso8601String(),
                ];
            },
            $this->getStandardAttributes('supermarket', 'halal_verify_certificate'),
        );
    }

    /**
     * Валидировать формат сертификата
     */
    private function validateCertificateFormat(string $certificateNumber): bool
    {
        // Basic validation - certificate should be at least 8 characters
        return strlen($certificateNumber) >= 8;
    }

    // ========================
    // ORDER COMPATIBILITY CHECK
    // ========================

    /**
     * Проверить совместимость заказа с халяль требованиями клиента
     */
    public function checkOrderHalalCompatibility(
        SupermarketOrder $order,
        array $productIds
    ): array {
        return $this->withSpan(
            'supermarket_halal.check_order_compatibility',
            function () use ($order, $productIds) {
                $customerHalal = $order->dietary_restrictions['halal'] ?? false;
                
                if (!$customerHalal) {
                    return [
                        'compatible' => true,
                        'requires_halal' => false,
                        'non_halal_products' => [],
                    ];
                }

                $nonHalalProducts = [];
                $nutritionData = ProductNutrition::whereIn('product_id', $productIds)
                    ->where('tenant_id', $order->tenant_id)
                    ->get();

                foreach ($nutritionData as $nutrition) {
                    if (!$nutrition->halal) {
                        $nonHalalProducts[] = [
                            'product_id' => $nutrition->product_id,
                            'name' => $this->getProductName($nutrition->product_id),
                        ];
                    }
                }

                $compatible = empty($nonHalalProducts);

                if (!$compatible) {
                    $order->update([
                        'allergen_warnings' => array_merge(
                            $order->allergen_warnings ?? [],
                            ['halal_compatibility_warning']
                        ),
                        'allergen_details' => array_merge(
                            $order->allergen_details ?? [],
                            [
                                'halal_warning' => 'Заказ содержит нехаляльные продукты',
                                'non_halal_count' => count($nonHalalProducts),
                            ]
                        ),
                    ]);
                }

                return [
                    'compatible' => $compatible,
                    'requires_halal' => $customerHalal,
                    'non_halal_products' => $nonHalalProducts,
                    'total_products' => count($productIds),
                    'halal_products' => count($productIds) - count($nonHalalProducts),
                ];
            },
            $this->getStandardAttributes('supermarket', 'halal_check_compatibility'),
        );
    }

    /**
     * Получить название продукта по ID
     */
    private function getProductName(int $productId): string
    {
        // TODO: Implement actual product name lookup
        return "Product #{$productId}";
    }

    // ========================
    // FILTERS AND SEARCH
    // ========================

    /**
     * Получить только халяль продукты
     */
    public function getHalalProducts(int $tenantId, ?int $businessGroupId = null): array
    {
        return $this->withSpan(
            'supermarket_halal.get_products',
            function () use ($tenantId, $businessGroupId) {
                $query = ProductNutrition::where('tenant_id', $tenantId)
                    ->where('business_group_id', $businessGroupId)
                    ->where('halal', true);

                return $query->get()
                    ->map(fn($nutrition) => [
                        'product_id' => $nutrition->product_id,
                        'halal' => $nutrition->halal,
                        'authority' => $nutrition->metadata['halal_authority'] ?? null,
                        'certificate_number' => $nutrition->metadata['halal_certificate_number'] ?? null,
                        'valid_until' => $nutrition->metadata['halal_valid_until'] ?? null,
                    ])
                    ->toArray();
            },
            $this->getStandardAttributes('supermarket', 'halal_get_products'),
        );
    }

    /**
     * Получить продукты с истекающим сроком халяль сертификата
     */
    public function getExpiringHalalCertificates(
        int $tenantId,
        ?int $businessGroupId = null,
        int $days = 30
    ): array {
        return $this->withSpan(
            'supermarket_halal.get_expiring',
            function () use ($tenantId, $businessGroupId, $days) {
                $expiryDate = now()->addDays($days);

                $query = ProductNutrition::where('tenant_id', $tenantId)
                    ->where('business_group_id', $businessGroupId)
                    ->where('halal', true)
                    ->whereNotNull('metadata->halal_valid_until');

                $expiring = $query->get()->filter(function ($nutrition) use ($expiryDate) {
                    $validUntil = $nutrition->metadata['halal_valid_until'] ?? null;
                    return $validUntil && CarbonImmutable::parse($validUntil)->lte($expiryDate);
                });

                return $expiring->map(fn($nutrition) => [
                    'product_id' => $nutrition->product_id,
                    'valid_until' => $nutrition->metadata['halal_valid_until'],
                    'days_until_expiry' => CarbonImmutable::parse($nutrition->metadata['halal_valid_until'])
                        ->diffInDays(now()),
                    'authority' => $nutrition->metadata['halal_authority'] ?? null,
                    'certificate_number' => $nutrition->metadata['halal_certificate_number'] ?? null,
                ])->toArray();
            },
            $this->getStandardAttributes('supermarket', 'halal_get_expiring'),
        );
    }

    // ========================
    // ANALYTICS
    // ========================

    /**
     * Получить статистику халяль продуктов
     */
    public function getHalalStatistics(
        int $tenantId,
        ?int $businessGroupId = null,
        int $days = 30
    ): array {
        return $this->withSpan(
            'supermarket_halal.get_statistics',
            function () use ($tenantId, $businessGroupId, $days) {
                $query = ProductNutrition::where('tenant_id', $tenantId)
                    ->where('business_group_id', $businessGroupId)
                    ->where('created_at', '>=', now()->subDays($days));

                $totalProducts = $query->count();
                $halalProducts = $query->where('halal', true)->count();
                $kosherProducts = $query->where('kosher', true)->count();
                $bothCertified = $query->where('halal', true)->where('kosher', true)->count();

                return [
                    'total_products' => $totalProducts,
                    'halal_products' => $halalProducts,
                    'halal_percentage' => $totalProducts > 0 ? round($halalProducts / $totalProducts * 100, 2) : 0,
                    'kosher_products' => $kosherProducts,
                    'kosher_percentage' => $totalProducts > 0 ? round($kosherProducts / $totalProducts * 100, 2) : 0,
                    'both_certified' => $bothCertified,
                    'no_certification' => $totalProducts - $halalProducts - $kosherProducts + $bothCertified,
                    'period_days' => $days,
                ];
            },
            $this->getStandardAttributes('supermarket', 'halal_statistics'),
        );
    }

    /**
     * Получить халяль статистику по заказам
     */
    public function getHalalOrderStatistics(
        int $tenantId,
        ?int $businessGroupId = null,
        int $days = 30
    ): array {
        return $this->withSpan(
            'supermarket_halal.get_order_statistics',
            function () use ($tenantId, $businessGroupId, $days) {
                $query = SupermarketOrder::where('tenant_id', $tenantId)
                    ->where('business_group_id', $businessGroupId)
                    ->where('created_at', '>=', now()->subDays($days));

                $totalOrders = $query->count();
                $halalOrders = $query->whereJsonContains('dietary_restrictions', ['halal'])->count();

                return [
                    'total_orders' => $totalOrders,
                    'halal_orders' => $halalOrders,
                    'halal_order_percentage' => $totalOrders > 0 ? round($halalOrders / $totalOrders * 100, 2) : 0,
                    'period_days' => $days,
                ];
            },
            $this->getStandardAttributes('supermarket', 'halal_order_statistics'),
        );
    }
}
