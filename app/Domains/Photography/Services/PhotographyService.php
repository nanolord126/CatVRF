<?php declare(strict_types=1);

namespace App\Domains\Photography\Services;


use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

use App\Domains\Photography\Models\Photographer;
use App\Domains\Photography\Models\PhotoStudio;
use Illuminate\Support\Str;

final readonly class PhotographyService
{
    public function __construct(private \App\Services\FraudControlService $fraud,
        private \App\Services\AuditService $audit,
        private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly Request $request, private readonly LoggerInterface $logger) {}

    /**
     * Общий скоринг популярности фотографа (ML Simulation)
     */
    public function calculatePhotographerScore(int $photographerId): float
    {
        $photographer = Photographer::findOrFail($photographerId);

        $experienceWeight = 0.4;
        $ratingWeight = 0.4;
        $availabilityWeight = 0.2;

        $baseScore = $photographer->experience_years * $experienceWeight;
        $ratingScore = 5.0 * $ratingWeight;
        $availabilityScore = $photographer->is_available ? 1.0 * $availabilityWeight : 0;

        $totalScore = $baseScore + $ratingScore + $availabilityScore;

        $this->logger->info('Photographer score recalculated', [
            'photographer_id' => $photographerId,
            'experience' => $photographer->experience_years,
            'total_score' => $totalScore,
            'correlation_id' => $this->request->header('X-Correlation-ID', (string) Str::uuid()),
        ]);

        return $totalScore;
    }

    /**
     * Проверка доступности студии (с учетом бронирований)
     */
    public function isStudioAvailable(int $studioId, string $startsAt, string $endsAt): bool
    {
        $studio = PhotoStudio::findOrFail($studioId);

        $overlapCount = $this->db->table('photography_bookings')
            ->where('studio_id', $studioId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($startsAt, $endsAt) {
                $q->whereBetween('starts_at', [$startsAt, $endsAt])
                  ->orWhereBetween('ends_at', [$startsAt, $endsAt]);
            })
            ->count();

        $this->logger->info('Studio availability checked', [
            'studio_id' => $studioId,
            'available' => $overlapCount === 0,
            'correlation_id' => $this->request->header('X-Correlation-ID', (string) Str::uuid()),
        ]);

        return $overlapCount === 0;
    }
}