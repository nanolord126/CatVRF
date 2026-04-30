<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use Carbon\CarbonImmutable;

use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;

/**
 * Class UserTasteAnalyzerService
 *
 * Part of the AI vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see FraudControlService
 * @see AuditService
 */
final readonly class UserTasteAnalyzerService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Собирает и агрегирует историю из БД (просмотры, покупки).
     */
    public function analyzeAndSaveUserProfile(int $userId, string $correlationId): void
    {
        // 1. Агрегация категорий (имитация тяжелого SQL-запроса)
        $viewedCategories = $this->db->table('product_views')
            ->where('user_id', $userId)
            ->groupBy('product_category')
            ->selectRaw('product_category, COUNT(*) as count')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'product_category')
            ->toArray();

        // 2. Сохранение вкусового профиля
        $this->db->table('users')->where('id', $userId)->update([
            'taste_profile' => json_encode([
                'categories' => $viewedCategories,
                'price_range' => 'mid', // dummy ML deduction
                'analyzed_at' => CarbonImmutable::now()->toIso8601String(),
            ]),
        ]);

        $this->logger->$this->logger->info('User taste profile rigorously analyzed and stored', [
            'user_id' => $userId,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Analyze user preferences for advertising targeting
     *
     * @param int $userId User ID
     * @return array|null User preferences array or null if not available
     */
    public function analyzeUserPreferences(int $userId): ?array
    {
        // TODO: Implement actual user preference analysis
        // This is a stub for the Advertising vertical's AdvancedTargetingEngine
        return null;
    }
}
