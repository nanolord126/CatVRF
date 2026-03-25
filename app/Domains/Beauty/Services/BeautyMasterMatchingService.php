<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\Master;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

/**
 * Сервис подбора мастеров красоты на основе AI Look.
 * CANON 2026: strict_types, final, readonly, correlation_id, audit, fraud-check.
 */
final readonly class BeautyMasterMatchingService
{
    public function __construct(
        private FraudControlService $fraudControl,
        private MasterAvailabilityService $availabilityService,
    ) {}

    /**
     * Поиск лучших мастеров для созданного образа.
     * 
     * @param array $look Результат генерации AI Beauty Look
     * @param int $userId ID пользователя
     * @param string $occasion Повод (party, wedding, daily, etc.)
     * @param Carbon|null $date Желаемая дата
     * @param string|null $correlationId
     * @return Collection
     */
    public function findBestMastersForLook(
        array $look,
        int $userId,
        string $occasion,
        ?Carbon $date = null,
        ?string $correlationId = null
    ): Collection {
        $correlationId ??= \Illuminate\Support\Str::uuid()->toString();
        $date ??= Carbon::now();

        // 1. Fraud Check
        $this->fraudControl->check([
            'user_id' => $userId,
            'action' => 'beauty_master_matching',
            'correlation_id' => $correlationId,
        ]);

        Log::channel('audit')->info('Starting master matching for AI look with availability', [
            'correlation_id' => $correlationId,
            'user_id' => $userId,
            'occasion' => $occasion,
        ]);

        // 2. Извлечение фич из образа
        $requiredSpecializations = $this->extractRequiredSpecializations($look);
        $preferredStyles = $this->extractStyles($look, $occasion);

        // 3. Запрос к базе с ранжированием
        $masters = Master::query()
            ->with(['salon'])
            ->where('tenant_id', tenant('id'))
            ->get();

        // 4. Ранжирование и проверка слотов
        return $masters->map(function (Master $master) use ($look, $preferredStyles, $requiredSpecializations, $date) {
            $score = $this->calculateMatchScore($master, $preferredStyles, $requiredSpecializations, $look);
            $slots = $this->availabilityService->getAvailableSlotsForLook($master, $look, $date);

            // Если слотов нет на сегодня, ищем на ближайшие дни (упрощенно здесь только $date)
            if ($slots->isEmpty()) {
                // В реальной логике здесь был бы цикл по дням
            }
            
            return [
                'master_id' => $master->id,
                'full_name' => $master->full_name,
                'salon_name' => $master->salon->name ?? 'Private Master',
                'match_score' => round($score, 2),
                'price_level' => $master->price_level ?? 1,
                'estimated_price' => $this->calculateEstimatedPrice($master, $look),
                'rating' => $master->rating,
                'available_slots' => $slots->values()->toArray(),
                'recommendation_reason' => $this->generateReason($master, $score, $look),
                'specialization' => $master->specialization,
            ];
        })
        ->filter(fn($m) => !empty($m['available_slots'])) // Показываем только тех, кто доступен
        ->sortByDesc('match_score')
        ->take(5)
        ->values();
    }

    private function calculateEstimatedPrice(Master $master, array $look): int
    {
        $base = match($master->price_level ?? 1) {
            4 => 1000000, // 10k rub
            3 => 500000,
            2 => 300000,
            default => 150000,
        };

        $multiplier = match($look['data']['makeup_style'] ?? 'daily') {
            'wedding' => 2.5,
            'evening' => 1.8,
            default => 1.0,
        };

        return (int)($base * $multiplier);
    }

    /**
     * Формула ранжирования 2026
     */
    private function calculateMatchScore(Master $master, array $preferredStyles, array $requiredSpecs, array $look): float
    {
        $score = 0.0;

        // 1. Соответствие специализации (Core) - вес 0.4
        $masterSpecs = collect($master->specializations_detailed ?? []);
        $specMatch = collect($requiredSpecs)->intersect($masterSpecs)->count() / max(count($requiredSpecs), 1);
        $score += $specMatch * 40;

        // 2. Соответствие стилю - вес 0.3
        $masterStyles = collect($master->preferred_styles ?? []);
        $styleMatch = collect($preferredStyles)->intersect($masterStyles)->count() / max(count($preferredStyles), 1);
        $score += $styleMatch * 30;

        // 3. Рейтинг и опыт - вес 0.2
        $score += ($master->rating / 5.0) * 15;
        $score += min($master->experience_years / 10.0, 1.0) * 5;

        // 4. Цена (соответствие профилю пользователя, если есть) - вес 0.1
        // В данном примере просто базовый бонус за премиум при сложных образах
        if (($look['confidence'] ?? 0) > 0.8 && ($master->price_level ?? 1) >= 3) {
            $score += 10;
        }

        return $score;
    }

    private function extractRequiredSpecializations(array $look): array
    {
        $specs = [];
        if (!empty($look['data']['makeup_style'])) $specs[] = 'makeup';
        if (!empty($look['data']['hairstyle'])) $specs[] = 'hair';
        return $specs;
    }

    private function extractStyles(array $look, string $occasion): array
    {
        $styles = [$occasion];
        if (!empty($look['data']['makeup_style'])) $styles[] = $look['data']['makeup_style'];
        return $styles;
    }

    private function generateReason(Master $master, float $score, array $look): string
    {
        if ($score > 80) return "Идеально подходит для вашего типа лица ({$look['data']['face_shape']}) и выбранного стиля.";
        if ($score > 60) return "Высокий рейтинг в категории " . ($master->specialization->first() ?? 'красота') . ".";
        return "Опытный мастер с хорошими отзывами.";
    }

    private function getMockAvailableSlots(Master $master, Carbon $date): array
    {
        return [
            $date->copy()->setHour(10)->format('H:i'),
            $date->copy()->setHour(14)->format('H:i'),
            $date->copy()->setHour(16)->format('H:i'),
        ];
    }
}
