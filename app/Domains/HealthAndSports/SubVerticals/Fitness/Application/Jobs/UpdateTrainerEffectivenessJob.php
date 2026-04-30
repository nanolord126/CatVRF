<?php

declare(strict_types=1);

namespace Modules\Fitness\Application\Jobs;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Fitness\Application\Services\TrainerEffectivenessService;
use Modules\Fitness\Infrastructure\Models\TrainerModel;

final readonly class UpdateTrainerEffectivenessJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;
    public int $timeout;

    public function __construct(
        public ?int $trainerId = null,
        public ?CarbonImmutable $periodStart = null,
        public ?CarbonImmutable $periodEnd = null,
    ) {
        $this->tries = 3;
        $this->timeout = 600;
    }

    public function handle(TrainerEffectivenessService $service): void
    {
        $periodStart = $this->periodStart ?? CarbonImmutable::now()->subDays(30);
        $periodEnd = $this->periodEnd ?? CarbonImmutable::now();

        $trainers = $this->trainerId
            ? [TrainerModel::findOrFail($this->trainerId)]
            : TrainerModel::where('is_active', true)->get();

        foreach ($trainers as $trainer) {
            try {
                $effectiveness = $service->calculateScore(
                    trainerId: $trainer->id,
                    periodStart: $periodStart,
                    periodEnd: $periodEnd,
                );

                $savedEffectiveness = $service->saveEffectiveness($effectiveness);

                // Check if score dropped below 65 - trigger notification
                if ($savedEffectiveness->requiresAttention()) {
                    $this->notifyManager($trainer, $savedEffectiveness);
                }

                // Check if score reached A+ - trigger bonus
                if ($savedEffectiveness->isTopPerformer()) {
                    $this->awardBonus($trainer, $savedEffectiveness);
                }

                Log::info('Trainer effectiveness updated', [
                    'trainer_id' => $trainer->id,
                    'score' => $savedEffectiveness->totalScore,
                    'level' => $savedEffectiveness->effectivenessLevel,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to update trainer effectiveness', [
                    'trainer_id' => $trainer->id,
                    'error' => $e->getMessage(),
                ]);

                // Continue with next trainer
                continue;
            }
        }

        Log::info('Trainer effectiveness batch update completed', [
            'trainers_processed' => count($trainers),
        ]);
    }

    private function notifyManager(object $trainer, object $effectiveness): void
    {
        // Dispatch notification to manager
        // This would integrate with your notification system
        Log::info('Manager notification triggered for low effectiveness', [
            'trainer_id' => $trainer->id,
            'score' => $effectiveness->totalScore,
            'level' => $effectiveness->effectivenessLevel,
        ]);

        // Create development plan task
        // This would integrate with your task management system
    }

    private function awardBonus(object $trainer, object $effectiveness): void
    {
        // Dispatch bonus award event
        Log::info('Bonus awarded for top performance', [
            'trainer_id' => $trainer->id,
            'score' => $effectiveness->totalScore,
            'level' => $effectiveness->effectivenessLevel,
        ]);

        // Give priority in schedule distribution
        // This would integrate with your scheduling system
    }
}
