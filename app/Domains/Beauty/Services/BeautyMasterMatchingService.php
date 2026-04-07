<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\BeautyMaster;
use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * BeautyMasterMatchingService — подбор лучших мастеров для AI-образа.
 *
 * Ранжирование мастеров по специализации, стилю, рейтингу
 * и доступным слотам с учётом fraud-проверки.
 */
final readonly class BeautyMasterMatchingService
{
    public function __construct(
        private FraudControlService $fraud,
        private MasterAvailabilityService $availabilityService,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {
    }

    /**
     * Поиск лучших мастеров для созданного образа.
     *
     * @param array $look Результат генерации AI Beauty Look
     * @param int $userId ID пользователя
     * @param string $occasion Повод (party, wedding, daily, etc.)
     * @param Carbon|null $date Желаемая дата
     * @param string|null $correlationId Идентификатор корреляции
     * @return Collection Коллекция мастеров с оценками совпадения
     */
    public function findBestMastersForLook(
        array $look,
        int $userId,
        string $occasion,
        ?Carbon $date = null,
        ?string $correlationId = null,
    ): Collection {
        $correlationId ??= Str::uuid()->toString();
        $date ??= Carbon::now();

        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'master_matching',
            amount: 0,
            correlationId: $correlationId,
        );

        $this->logger->info('Starting master matching for AI look with availability', [
            'correlation_id' => $correlationId,
            'user_id' => $userId,
            'occasion' => $occasion,
        ]);

        $requiredSpecializations = $this->extractRequiredSpecializations($look);
        $preferredStyles = $this->extractStyles($look, $occasion);

        /** @var Collection<int, BeautyMaster> $masters */
        $masters = BeautyMaster::query()
            ->with(['salon'])
            ->get();

        return $masters->map(function (BeautyMaster $master) use ($look, $preferredStyles, $requiredSpecializations, $date): array {
            $score = $this->calculateMatchScore($master, $preferredStyles, $requiredSpecializations, $look);
            $slots = $this->availabilityService->getAvailableSlotsForLook($master, $look, $date);

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
        ->filter(fn (array $m): bool => !empty($m['available_slots']))
        ->sortByDesc('match_score')
        ->take(5)
        ->values();
    }

    /**
     * Расчёт предположительной цены для мастера по образу.
     */
    private function calculateEstimatedPrice(BeautyMaster $master, array $look): int
    {
        $base = match ($master->price_level ?? 1) {
            4 => 1000000,
            3 => 500000,
            2 => 300000,
            default => 150000,
        };

        $multiplier = match ($look['data']['makeup_style'] ?? 'daily') {
            'evening' => 1.8,
            default => 1.0,
        };

        return (int) ($base * $multiplier);
    }

    /**
     * Формула ранжирования 2026 — оценка соответствия мастера образу.
     */
    private function calculateMatchScore(
        BeautyMaster $master,
        array $preferredStyles,
        array $requiredSpecs,
        array $look,
    ): float {
        $score = 0.0;

        $masterSpecs = collect($master->specializations_detailed ?? []);
        $specMatch = collect($requiredSpecs)->intersect($masterSpecs)->count() / max(count($requiredSpecs), 1);
        $score += $specMatch * 40;

        $masterStyles = collect($master->preferred_styles ?? []);
        $styleMatch = collect($preferredStyles)->intersect($masterStyles)->count() / max(count($preferredStyles), 1);
        $score += $styleMatch * 30;

        $score += ($master->rating / 5.0) * 15;
        $score += min($master->experience_years / 10.0, 1.0) * 5;

        if (($look['confidence'] ?? 0) > 0.8 && ($master->price_level ?? 1) >= 3) {
            $score += 10;
        }

        return $score;
    }

    /**
     * Извлечь требуемые специализации из образа.
     *
     * @return array<int, string>
     */
    private function extractRequiredSpecializations(array $look): array
    {
        $specs = [];

        if (!empty($look['data']['makeup_style'])) {
            $specs[] = 'makeup';
        }

        if (!empty($look['data']['hairstyle'])) {
            $specs[] = 'hair';
        }

        return $specs;
    }

    /**
     * Извлечь предпочитаемые стили.
     *
     * @return array<int, string>
     */
    private function extractStyles(array $look, string $occasion): array
    {
        $styles = [$occasion];

        if (!empty($look['data']['makeup_style'])) {
            $styles[] = $look['data']['makeup_style'];
        }

        return $styles;
    }

    /**
     * Сгенерировать причину рекомендации мастера.
     */
    private function generateReason(BeautyMaster $master, float $score, array $look): string
    {
        if ($score > 80) {
            return "Идеально подходит для вашего типа лица ({$look['data']['face_shape']}) и выбранного стиля.";
        }

        if ($score > 60) {
            return 'Высокий рейтинг в категории ' . ($master->specialization ?? 'красота') . '.';
        }

        return 'Опытный мастер с хорошими отзывами.';
    }
}
