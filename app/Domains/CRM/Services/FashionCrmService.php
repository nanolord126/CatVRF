<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;


use Illuminate\Support\Facades\DB;
use App\Domains\CRM\DTOs\CreateCrmInteractionDto;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmFashionProfile;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;

/**
 * FashionCrmService — CRM-логика для вертикали Fashion/Одежда.
 *
 * Цветотип, размеры, капсульные гардеробы, AR-примерки,
 * wishlist, предпочтения брендов и стилей.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class FashionCrmService
{
    public function __construct(
        private CrmService $crmService,
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $logger,
    
    ) {}

    /**
     * Создать fashion-профиль CRM-клиента.
     */
    public function createFashionProfile(
        int $crmClientId,
        int $tenantId,
        string $correlationId,
        ?string $bodyType = null,
        ?string $colorType = null,
        ?string $styleType = null,
        array $sizes = [],
        array $preferredBrands = [],
        array $preferredColors = [],
        ?string $notes = null
    ): CrmFashionProfile {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_fashion_profile_create',
            amount: 0,
            correlationId: $correlationId
    );

        return $this->db->transaction(function () use (
            $crmClientId, $tenantId, $correlationId, $bodyType, $colorType,
            $styleType, $sizes, $preferredBrands, $preferredColors, $notes
    ): CrmFashionProfile {
            $profile = CrmFashionProfile::query()->create([
                'crm_client_id' => $crmClientId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'body_type' => $bodyType,
                'color_type' => $colorType,
                'style_type' => $styleType,
                'sizes' => $sizes,
                'preferred_brands' => $preferredBrands,
                'preferred_colors' => $preferredColors,
                'disliked_styles' => [],
                'wardrobe_capsules' => [],
                'wishlist' => [],
                'ar_tryons_count' => 0,
                'ar_tryons_history' => [],
                'seasonal_preferences' => [],
                'notes' => $notes,
            ]);

            $this->logger->info('Fashion CRM profile created', [
                'profile_id' => $profile->id,
                'client_id' => $crmClientId,
                'color_type' => $colorType,
                'style_type' => $styleType,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_fashion_profile_created',
                CrmFashionProfile::class,
                $profile->id,
                [],
                $profile->toArray(),
                $correlationId
    );

            return $profile;
        });
    }

    /**
     * Добавить товар в wishlist.
     */
    public function addToWishlist(
        CrmFashionProfile $profile,
        string $productId,
        string $productName,
        string $correlationId,
        ?float $price = null
    ): CrmFashionProfile {
        return $this->db->transaction(function () use ($profile, $productId, $productName, $correlationId, $price): CrmFashionProfile {
            $wishlist = $profile->wishlist ?? [];

            foreach ($wishlist as $item) {
                if (($item['product_id'] ?? '') === $productId) {
                    return $profile;
                }
            }

            $wishlist[] = [
                'product_id' => $productId,
                'name' => $productName,
                'price' => $price,
                'added_at' => now()->toDateString(),
            ];

            $profile->update(['wishlist' => $wishlist]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Fashion wishlist item added', [
                'profile_id' => $profile->id,
                'product_id' => $productId,
                'correlation_id' => $correlationId,
            ]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Сохранить капсульный гардероб.
     */
    public function saveCapsuleWardrobe(
        CrmFashionProfile $profile,
        string $capsuleName,
        array $items,
        string $correlationId,
        ?string $season = null,
        ?string $occasion = null
    ): CrmFashionProfile {
        return $this->db->transaction(function () use ($profile, $capsuleName, $items, $correlationId, $season, $occasion): CrmFashionProfile {
            $capsules = $profile->wardrobe_capsules ?? [];
            $capsules[] = [
                'name' => $capsuleName,
                'items' => $items,
                'season' => $season,
                'occasion' => $occasion,
                'created_at' => now()->toDateString(),
            ];

            $profile->update(['wardrobe_capsules' => $capsules]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_fashion_capsule_created',
                CrmFashionProfile::class,
                $profile->id,
                [],
                ['capsule' => $capsuleName, 'items_count' => count($items)],
                $correlationId
    );

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Зафиксировать AR-примерку.
     */
    public function recordArTryOn(
        CrmFashionProfile $profile,
        string $productId,
        string $correlationId,
        ?bool $liked = null
    ): CrmFashionProfile {
        return $this->db->transaction(function () use ($profile, $productId, $correlationId, $liked): CrmFashionProfile {
            $history = $profile->ar_tryons_history ?? [];
            $history[] = [
                'product_id' => $productId,
                'liked' => $liked,
                'date' => now()->toDateString(),
            ];

            $profile->update([
                'ar_tryons_history' => $history,
                'ar_tryons_count' => ($profile->ar_tryons_count ?? 0) + 1,
            ]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $profile->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            return $profile->fresh() ?? $profile;
        });
    }

    /**
     * Записать покупку одежды.
     */
    public function recordPurchase(
        CrmClient $client,
        string $productName,
        float $amount,
        string $correlationId,
        ?string $size = null,
        ?string $brand = null
    ): void {
        $this->db->transaction(function () use ($client, $productName, $amount, $correlationId, $size, $brand): void {
            $this->crmService->recordInteraction(
                new CreateCrmInteractionDto(
                    crmClientId: $client->id,
                    tenantId: $client->tenant_id,
                    correlationId: $correlationId,
                    type: 'order',
                    channel: 'marketplace',
                    direction: 'inbound',
                    content: "Покупка одежды: {$productName}",
                    metadata: [
                        'product' => $productName,
                        'amount' => $amount,
                        'size' => $size,
                        'brand' => $brand,
                    ]
    )
    );

            $client->increment('total_orders');
            $client->increment('total_spent', $amount);
            $client->update(['last_order_at' => now()]);
        });
    }

    /**
     * Рекомендации по цветотипу клиента.
     */
    public function getColorTypeRecommendations(CrmFashionProfile $profile): array
    {
        $colorType = $profile->color_type;
        $recommendations = [];

        $colorMap = [
            'spring' => ['тёплые оттенки', 'коралловый', 'персиковый', 'золотой', 'тёплый зелёный'],
            'summer' => ['холодные пастельные', 'лавандовый', 'розовый', 'голубой', 'серый'],
            'autumn' => ['тёплые глубокие', 'бордовый', 'оливковый', 'горчичный', 'шоколадный'],
            'winter' => ['чистые яркие', 'чёрный', 'белый', 'красный', 'синий', 'изумрудный'],
        ];

        if ($colorType !== null && isset($colorMap[$colorType])) {
            $recommendations = [
                'color_type' => $colorType,
                'recommended_colors' => $colorMap[$colorType],
                'tip' => "Для цветотипа «{$colorType}» рекомендуем: " . implode(', ', $colorMap[$colorType]),
            ];
        }

        return $recommendations;
    }

    /**
     * «Спящие» fashion-клиенты.
     */
    public function getSleepingClients(int $tenantId, int $daysInactive = 60): Collection
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->byVertical('fashion')
            ->sleeping($daysInactive)
            ->orderByDesc('total_spent')
            ->get();
    }

    /**
     * Выполнить операцию внутри транзакции.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    protected function executeInTransaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
