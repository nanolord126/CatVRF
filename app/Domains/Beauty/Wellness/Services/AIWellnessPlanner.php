<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Wellness\Services;



use Carbon\Carbon;
use Psr\Log\LoggerInterface;

final readonly class AIWellnessPlanner
{
    public function __construct(
        private \App\Services\AI\AIConstructorService $aiConstructor,
        private \App\Services\RecommendationService $recommendation,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
    ) {}

        /**
         * Generate a personalized wellness program based on client data and specialist expertise.
         * @throws \RuntimeException
         */
        public function generateProgram(
            int $client_id,
            int $specialist_id,
            array $health_goals,
            array $medical_notes = []
        ): WellnessProgram {
            $correlationId = (string) Str::uuid();

            // 1. Audit Init
            $this->logger->info('AI Wellness Planner Generation Init', [
                'client_id' => $client_id,
                'specialist_id' => $specialist_id,
                'correlation_id' => $correlationId,
            ]);

            // 2. Fraud Check - for intensive AI resource usage
            // AIQuotaService should be used here if available

            // 3. AI Execution (Vision/Text analysis)
            $aiResult = $this->aiConstructor->analyzePhotoAndRecommend(
                photo: null, // Image processing can be added via payload
                vertical: 'wellness',
                userId: $client_id,
            );

            // 4. Transform AI Data to Wellness Model (Canon 2026)
            $programData = [
                'nutrition_plan' => $aiResult['recommendations']['nutrition'] ?? [],
                'exercise_schedule' => $aiResult['recommendations']['exercises'] ?? [],
                'routine' => $aiResult['recommendations']['daily_routine'] ?? [],
                'ai_score' => $aiResult['analysis']['confidence'] ?? 0.0,
                'goals' => $health_goals,
            ];

            // 5. DB Transaction (Mutations)
            return $this->db->transaction(function () use ($client_id, $specialist_id, $programData, $medical_notes, $correlationId) {
                $program = WellnessProgram::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => tenant()->id,
                    'center_id' => $this->center->id,
                    'client_id' => $client_id,
                    'specialist_id' => $specialist_id,
                    'name' => "Personalized AI Wellness Plan - " . Carbon::now()->format('Y-m-d'),
                    'program_data' => $programData,
                    'medical_restrictions' => $medical_notes,
                    'health_goal' => implode(', ', $programData['goals']),
                    'start_at' => Carbon::now(),
                    'end_at' => Carbon::now()->addMonths(3), // Standard 3 month plan
                    'correlation_id' => $correlationId,
                ]);

                // 6. Audit Exit
                $this->logger->info('AI Wellness Plan Finalized', [
                    'program_uuid' => $program->uuid,
                    'correlation_id' => $correlationId,
                    'score' => $programData['ai_score'],
                ]);

                return $program;
            });
        }

        /**
         * Get specific recommendations for services available in the center.
         */
        public function recommendServices(int $client_id): Collection
        {
            return $this->recommendation->getForUser(
                 userId: $client_id,
                 vertical: 'wellness',
                 context: ['center_id' => $this->center->id],
            );
        }
}
