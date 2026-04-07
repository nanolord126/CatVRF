<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Services\AI;

use App\Domains\Inventory\Services\InventoryService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\RecommendationService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * AI-конструктор отеля для вертикали Hotels.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 *
 * Анализ предпочтений + UserTasteProfile → рекомендации отелей и номеров
 * → формирование персонального пакета → 3D-тур.
 *
 * @package App\Domains\Hotels\Services\AI
 */
final readonly class HotelConstructorService
{
    public function __construct(
        private FraudControlService $fraud,
        private RecommendationService $recommendation,
        private InventoryService $inventory,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private AuditService $audit,
        private CacheRepository $cache,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    /**
     * Анализировать предпочтения и рекомендовать отели/номера.
     *
     * @param array<string, mixed> $preferences Предпочтения пользователя
     * @param int $userId ID пользователя
     * @param int $tenantId ID текущего tenant
     * @param string $correlationId ID корреляции запроса
     *
     * @return array<string, mixed> Результат анализа и рекомендации
     */
    public function analyzePreferencesAndRecommend(
        array $preferences,
        int $userId,
        int $tenantId,
        string $correlationId = '',
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'hotel_ai_constructor',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $cacheKey = "user_ai_designs:hotel:{$userId}:" . md5((string) json_encode($preferences));

        return $this->cache->remember($cacheKey, 3600, function () use ($preferences, $userId, $tenantId, $correlationId) {
            return $this->db->transaction(function () use ($preferences, $userId, $tenantId, $correlationId) {

                // 1. Мерджим с UserTasteProfile
                $taste = $this->tasteAnalyzer->getProfile($userId);
                $fullProfile = array_merge($preferences, $taste->hotel_preferences ?? []);

                // 2. Рекомендации отелей и номеров
                $recommendations = collect($this->recommendation->getForUser(
                    userId: $userId,
                    vertical: 'hotels',
                    context: $fullProfile,
                ))->toArray();

                // 3. Проверка реального наличия номеров + 3D-туры
                foreach ($recommendations as &$item) {
                    $roomId = (int) ($item['room_id'] ?? 0);
                    $item['available_rooms'] = $roomId > 0
                        ? $this->inventory->getAvailableStock($roomId)
                        : 0;
                    $item['virtual_tour_url'] = $roomId > 0
                        ? '/hotels/3d-tour/' . $roomId . '/' . $userId
                        : null;
                }
                unset($item);

                // 4. Формирование персонального пакета (номер + услуги)
                $package = $this->buildPersonalPackage($recommendations, $fullProfile);

                // 5. Сохранение в user_ai_designs
                $this->saveDesign($userId, $fullProfile, $package, $correlationId);

                // 6. Audit-лог
                $this->audit->log(
                    action: 'hotel_ai_constructor_used',
                    subjectType: 'user_ai_designs',
                    subjectId: $userId,
                    old: [],
                    new: ['preferences' => $preferences, 'package_items' => count($package)],
                    correlationId: $correlationId,
                );

                $this->logger->info('Hotel AI constructor completed', [
                    'user_id' => $userId,
                    'tenant_id' => $tenantId,
                    'rec_count' => count($recommendations),
                    'package_items' => count($package),
                    'correlation_id' => $correlationId,
                ]);

                return [
                    'success' => true,
                    'profile' => $fullProfile,
                    'package' => $package,
                    'recommended' => $recommendations,
                    'correlation_id' => $correlationId,
                ];
            });
        });
    }

    /**
     * Формирует персональный пакет: номер + услуги.
     *
     * @param array<int, array<string, mixed>> $recommendations Рекомендации
     * @param array<string, mixed> $profile Профиль пользователя
     *
     * @return array<int, array<string, mixed>> Сформированный пакет
     */
    private function buildPersonalPackage(array $recommendations, array $profile): array
    {
        $package = [];

        $rooms = array_filter($recommendations, fn(array $r): bool => ($r['type'] ?? '') === 'room');
        $services = array_filter($recommendations, fn(array $r): bool => ($r['type'] ?? '') === 'service');

        if (!empty($rooms)) {
            $package[] = array_values($rooms)[0];
        }

        foreach (array_slice(array_values($services), 0, 3) as $service) {
            $package[] = $service;
        }

        // Учитываем бюджет из профиля для фильтрации
        $maxBudget = (int) ($profile['max_budget'] ?? 0);
        if ($maxBudget > 0) {
            $package = array_filter(
                $package,
                fn(array $item): bool => ((int) ($item['price'] ?? 0)) <= $maxBudget,
            );
            $package = array_values($package);
        }

        return $package;
    }

    /**
     * Сохранить результат в user_ai_designs.
     */
    private function saveDesign(int $userId, array $profile, array $package, string $correlationId): void
    {
        $now = Carbon::now()->toDateTimeString();

        $this->db->table('user_ai_designs')->updateOrInsert(
            ['user_id' => $userId, 'vertical' => 'hotel'],
            [
                'design_data' => json_encode(
                    ['profile' => $profile, 'package' => $package],
                    JSON_UNESCAPED_UNICODE,
                ),
                'correlation_id' => $correlationId,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );
    }
}
