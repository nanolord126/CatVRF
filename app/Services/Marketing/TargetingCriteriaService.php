<?php declare(strict_types=1);

namespace App\Services\Marketing;


use Illuminate\Http\Request;
use App\Services\AuditService;
use App\Services\FraudControl\FraudControlService;
use App\Services\ML\AnonymizationService;
use App\Services\ML\UserBehaviorAnalyzerService;


use Illuminate\Support\Str;
use Illuminate\Database\DatabaseManager;

/**
 * TargetingCriteriaService — критерии таргетинга для рекламы и рассылок.
 *
 * Строится ТОЛЬКО на обезличенных данных + UserTasteProfile.
 * Raw user_id в рекламных отчётах и ClickHouse запрещён.
 *
 * Параметры:
 *  - UserTasteProfile (категории, бренды, цвета, цена)
 *  - New / Returning
 *  - B2C / B2B
 *  - Behavior patterns (AR usage, AI-constructor usage)
 *  - Geo (hashed city), Device type, Time of day, LTV segment
 */
final readonly class TargetingCriteriaService
{
    public function __construct(
        private readonly Request $request,
        private UserBehaviorAnalyzerService $userBehaviorAnalyzer,
        private AnonymizationService        $anonymizer,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Составить таргетинговый профиль пользователя.
     * НЕ содержит user_id в выходных данных.
     */
    public function match(int $userId, string $vertical): array
    {
        $isNew   = $this->userBehaviorAnalyzer->isNewUser($userId);
        $pattern = $this->userBehaviorAnalyzer->getPattern($userId, $isNew);

        // Taste profile (агрегированные данные)
        $tasteProfile = $this->db->table('user_taste_profiles')
            ->where('user_id', $userId)
            ->first();

        $priceRange = null;
        $favBrands  = [];
        $categories = [];

        if ($tasteProfile !== null) {
            $data       = is_array($tasteProfile->profile_data ?? null)
                ? $tasteProfile->profile_data
                : (json_decode($tasteProfile->profile_data ?? '{}', true) ?? []);

            $priceRange = $data['price_range'] ?? null;
            $favBrands  = $data['favorite_brands'] ?? [];
            $categories = $data['categories'] ?? [];
        }

        // Определяем B2B (по наличию данных, без facade Auth)
        $isB2B = $this->db->table('business_groups')
            ->whereExists(fn ($q) => $q->from('users')
                ->whereColumn('users.active_business_group_id', 'business_groups.id')
                ->where('users.id', $userId))
            ->exists();

        return [
            // Анонимизированный идентификатор (не raw user_id!)
            'taste_score'            => (float) ($categories[$vertical] ?? 0),
            'is_new_user'            => $isNew,
            'is_b2b'                 => $isB2B,
            'price_range'            => $priceRange,
            'favorite_brands'        => $favBrands,
            'device'                 => $pattern['device_type'] ?? 'unknown',
            'city_hash'              => $this->anonymizer->hashCity($this->request->header('X-User-City', '')),
            'days_since_last_activity' => $pattern['days_since_last_activity'] ?? 0,
            'is_churn_risk'          => $pattern['is_churn_risk'] ?? false,
            'ltv_segment'            => $this->getLtvSegment($userId),
            'hour_of_day'            => (int) now()->format('H'),
            'day_of_week'            => (int) now()->dayOfWeek,
        ];
    }

    // ─── Сегментация по LTV ──────────────────────────────────────────────────

    private function getLtvSegment(int $userId): string
    {
        $totalSpent = (float) $this->db->table('orders')
            ->where('user_id', $userId)
            ->whereIn('status', ['completed', 'delivered'])
            ->sum('total_amount');

        return match (true) {
            $totalSpent >= 100000 => 'vip',       // 100k+ руб
            $totalSpent >= 10000  => 'loyal',      // 10k+ руб
            $totalSpent >= 1000   => 'regular',    // 1k+ руб
            $totalSpent > 0       => 'new_buyer',  // есть покупки
            default               => 'prospect',   // нет покупок
        };
    }
}
