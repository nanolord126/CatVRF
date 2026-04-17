<?php declare(strict_types=1);

namespace App\Domains\Education\Services\AI;

use App\Domains\Education\DTOs\CreateLearningPathDto;
use App\Domains\Education\DTOs\LearningPathRecommendationDto;
use App\Domains\Education\Models\Course;
use App\Domains\Education\Models\Enrollment;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\Security\IdempotencyService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\RecommendationService;
use App\Services\ML\AnonymizationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

final readonly class EducationLearningPathAIConstructorService
{
    private const CACHE_TTL = 3600;
    private const EMBEDDING_DIMENSION = 1536;

    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private IdempotencyService $idempotency,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private RecommendationService $recommendation,
        private AnonymizationService $anonymizer,
    ) {}

    public function generatePersonalizedLearningPath(CreateLearningPathDto $dto): LearningPathRecommendationDto
    {
        $this->fraud->check($dto);

        if ($dto->idempotencyKey !== null) {
            $cached = $this->idempotency->check('learning_path_generation', $dto->idempotencyKey, $dto->toArray(), $dto->tenantId);
            if (!empty($cached)) {
                return LearningPathRecommendationDto::fromArray($cached);
            }
        }

        $cacheKey = $dto->toCacheKey();
        $cachedResult = Redis::get($cacheKey);

        if ($cachedResult !== null) {
            Log::channel('audit')->info('Learning path served from cache', [
                'correlation_id' => $dto->correlationId,
                'cache_key' => $cacheKey,
                'user_id' => $dto->userId,
            ]);

            return LearningPathRecommendationDto::fromArray(json_decode($cachedResult, true));
        }

        return DB::transaction(function () use ($dto, $cacheKey) {
            $course = Course::findOrFail($dto->courseId);

            $user = \App\Models\User::findOrFail($dto->userId);
        $this->tasteAnalyzer->analyzeAndSaveUserProfile($user);
        $userBehavior = $this->tasteAnalyzer->getProfile($dto->userId) ?? [];
            $userLearningStyle = $this->analyzeLearningStyle($dto, $userBehavior);
            
            $courseEmbedding = $this->generateCourseEmbedding($course);
            $userEmbedding = $this->generateUserEmbedding($dto, $userLearningStyle);
            
            $similarityScore = $this->calculateSimilarity($courseEmbedding, $userEmbedding);
            
            $learningPath = $this->constructAdaptivePath($course, $dto, $userLearningStyle, $similarityScore);
            
            $completionProbability = $this->predictCompletionProbability($dto, $learningPath, $similarityScore);
            
            $recommendation = new LearningPathRecommendationDto(
                pathId: (string) Str::uuid(),
                modules: $learningPath['modules'],
                estimatedHours: $learningPath['estimated_hours'],
                estimatedWeeks: $learningPath['estimated_weeks'],
                difficultyLevel: $learningPath['difficulty_level'],
                adaptiveAdjustments: $learningPath['adaptive_adjustments'],
                recommendedResources: $this->getRecommendedResources($dto, $course),
                completionProbability: $completionProbability,
                milestones: $this->generateMilestones($learningPath['modules']),
                generatedAt: now()->toIso8601String(),
            );

            Redis::setex($cacheKey, self::CACHE_TTL, json_encode($recommendation->toArray()));

            if ($dto->idempotencyKey !== null) {
                $this->idempotency->record('learning_path_generation', $dto->idempotencyKey, $dto->toArray(), $recommendation->toArray(), $dto->tenantId, 1440);
            }

            $this->audit->record('education_learning_path_generated', 'LearningPathRecommendationDto', null, [], [
                'correlation_id' => $dto->correlationId,
                'tenant_id' => $dto->tenantId,
                'user_id' => $dto->userId,
                'course_id' => $dto->courseId,
                'path_id' => $recommendation->pathId,
                'similarity_score' => $similarityScore,
                'completion_probability' => $completionProbability,
                'is_corporate' => $dto->isCorporate,
            ], $dto->correlationId);

            $this->saveUserAILearningDesign($dto, $recommendation, $similarityScore);

            return $recommendation;
        });
    }

    private function analyzeLearningStyle(CreateLearningPathDto $dto, array $userBehavior): array
    {
        $prompt = $this->buildLearningStylePrompt($dto, $userBehavior);

        $analysis = $this->simulateAIAnalysis($prompt);

        Log::channel('audit')->info('Learning style analyzed', [
            'correlation_id' => $dto->correlationId,
            'user_id' => $dto->userId,
            'analysis' => $this->anonymizer->anonymizeEvent(['learning_style' => $analysis]),
        ]);

        return $analysis;
    }

    private function buildLearningStylePrompt(CreateLearningPathDto $dto, array $userBehavior): string
    {
        $prompt = "Analyze learning style for user ID {$dto->userId}.\n\n";
        
        if ($dto->learningGoal !== null) {
            $prompt .= "Learning Goal: {$dto->learningGoal}\n";
        }
        
        if ($dto->currentLevel !== null) {
            $prompt .= "Current Level: {$dto->currentLevel}\n";
        }
        
        if ($dto->targetLevel !== null) {
            $prompt .= "Target Level: {$dto->targetLevel}\n";
        }
        
        if ($dto->weeklyHours !== null) {
            $prompt .= "Weekly Study Hours: {$dto->weeklyHours}\n";
        }
        
        if ($dto->preferredTopics !== null && count($dto->preferredTopics) > 0) {
            $prompt .= "Preferred Topics: " . implode(', ', $dto->preferredTopics) . "\n";
        }
        
        if ($dto->learningStyle !== null) {
            $prompt .= "Self-Reported Style: {$dto->learningStyle}\n";
        }

        $prompt .= "\nUser Behavior Data:\n";
        $prompt .= "Session Count: " . ($userBehavior['session_count'] ?? 0) . "\n";
        $prompt .= "Avg Session Duration: " . ($userBehavior['avg_session_duration'] ?? 0) . " minutes\n";
        $prompt .= "Completion Rate: " . ($userBehavior['completion_rate'] ?? 0) . "%\n";
        $prompt .= "Preferred Content Types: " . implode(', ', $userBehavior['preferred_content_types'] ?? []) . "\n";
        $prompt .= "Peak Learning Hours: " . implode(', ', $userBehavior['peak_hours'] ?? []) . "\n";

        return $prompt;
    }

    private function generateCourseEmbedding(Course $course): array
    {
        $text = $course->title . ' ' . $course->description . ' ' . $course->level;
        
        if ($course->syllabus !== null && is_array($course->syllabus)) {
            $text .= ' ' . implode(' ', $course->syllabus);
        }

        return $this->simulateEmbedding($text);
    }

    private function generateUserEmbedding(CreateLearningPathDto $dto, array $learningStyle): array
    {
        $text = "User learning profile: ";
        
        if ($dto->learningGoal !== null) {
            $text .= "Goal: {$dto->learningGoal}. ";
        }
        
        if ($dto->currentLevel !== null) {
            $text .= "Current: {$dto->currentLevel}. ";
        }
        
        if ($dto->targetLevel !== null) {
            $text .= "Target: {$dto->targetLevel}. ";
        }

        $text .= "Visual: {$learningStyle['visual_score']}, ";
        $text .= "Auditory: {$learningStyle['auditory_score']}, ";
        $text .= "Kinesthetic: {$learningStyle['kinesthetic_score']}, ";
        $text .= "Reading: {$learningStyle['reading_score']}. ";
        $text .= "Pace: {$learningStyle['preferred_pace']}. ";
        $text .= "Attention: {$learningStyle['attention_span_minutes']}min. ";
        $text .= "Difficulty: {$learningStyle['difficulty_preference']}. ";

        if ($dto->preferredTopics !== null && count($dto->preferredTopics) > 0) {
            $text .= "Topics: " . implode(', ', $dto->preferredTopics);
        }

        return $this->simulateEmbedding($text);
    }

    private function calculateSimilarity(array $embedding1, array $embedding2): float
    {
        $dotProduct = 0.0;
        $norm1 = 0.0;
        $norm2 = 0.0;

        for ($i = 0; $i < count($embedding1); $i++) {
            $dotProduct += $embedding1[$i] * $embedding2[$i];
            $norm1 += $embedding1[$i] ** 2;
            $norm2 += $embedding2[$i] ** 2;
        }

        $norm1 = sqrt($norm1);
        $norm2 = sqrt($norm2);

        if ($norm1 === 0.0 || $norm2 === 0.0) {
            return 0.0;
        }

        return $dotProduct / ($norm1 * $norm2);
    }

    private function constructAdaptivePath(Course $course, CreateLearningPathDto $dto, array $learningStyle, float $similarityScore): array
    {
        $modules = $course->modules()->orderBy('order')->get();
        
        $adaptedModules = [];
        $totalHours = 0;
        $adaptiveAdjustments = [];

        foreach ($modules as $index => $module) {
            $moduleHours = $this->estimateModuleHours($module, $learningStyle);
            $totalHours += $moduleHours;

            $moduleAdjustments = $this->getModuleAdaptations($module, $learningStyle, $similarityScore);
            
            if (count($moduleAdjustments) > 0) {
                $adaptiveAdjustments[$module->id] = $moduleAdjustments;
            }

            $adaptedModules[] = [
                'module_id' => $module->id,
                'module_title' => $module->title,
                'order' => $module->order,
                'estimated_hours' => $moduleHours,
                'content_format' => $this->selectContentFormat($learningStyle),
                'difficulty' => $this->adjustDifficulty($module->difficulty ?? 'medium', $learningStyle, $similarityScore),
                'prerequisites' => $module->prerequisites ?? [],
                'learning_objectives' => $module->learning_objectives ?? [],
                'has_interactive_elements' => $learningStyle['kinesthetic_score'] > 0.6,
                'has_video_content' => $learningStyle['visual_score'] > 0.5 || $learningStyle['auditory_score'] > 0.5,
                'has_reading_material' => $learningStyle['reading_score'] > 0.4,
            ];
        }

        $weeklyHours = $dto->weeklyHours ?? 10;
        $estimatedWeeks = (int) ceil($totalHours / $weeklyHours);

        $difficultyLevel = $this->calculateOverallDifficulty($adaptedModules, $learningStyle, $similarityScore);

        return [
            'modules' => $adaptedModules,
            'estimated_hours' => $totalHours,
            'estimated_weeks' => $estimatedWeeks,
            'difficulty_level' => $difficultyLevel,
            'adaptive_adjustments' => $adaptiveAdjustments,
        ];
    }

    private function estimateModuleHours($module, array $learningStyle): int
    {
        $baseHours = $module->estimated_hours ?? 5;
        
        $paceMultiplier = match ($learningStyle['preferred_pace']) {
            'slow' => 1.3,
            'fast' => 0.8,
            default => 1.0,
        };

        $attentionMultiplier = $learningStyle['attention_span_minutes'] < 30 ? 1.2 : 1.0;

        return (int) ceil($baseHours * $paceMultiplier * $attentionMultiplier);
    }

    private function getModuleAdaptations($module, array $learningStyle, float $similarityScore): array
    {
        $adaptations = [];

        if ($learningStyle['visual_score'] > 0.7) {
            $adaptations[] = 'add_visual_aids';
            $adaptations[] = 'include_infographics';
        }

        if ($learningStyle['auditory_score'] > 0.7) {
            $adaptations[] = 'add_audio_explanations';
            $adaptations[] = 'include_podcasts';
        }

        if ($learningStyle['kinesthetic_score'] > 0.7) {
            $adaptations[] = 'add_interactive_exercises';
            $adaptations[] = 'include_hands_on_projects';
        }

        if ($learningStyle['reading_score'] > 0.7) {
            $adaptations[] = 'provide_detailed_text';
            $adaptations[] = 'include_reading_lists';
        }

        if ($similarityScore < 0.5) {
            $adaptations[] = 'add_prerequisites_review';
            $adaptations[] = 'include_foundation_materials';
        }

        if ($learningStyle['social_learning_score'] > 0.6) {
            $adaptations[] = 'add_group_activities';
            $adaptations[] = 'include_peer_review';
        }

        return $adaptations;
    }

    private function selectContentFormat(array $learningStyle): string
    {
        $scores = [
            'video' => ($learningStyle['visual_score'] + $learningStyle['auditory_score']) / 2,
            'interactive' => $learningStyle['kinesthetic_score'],
            'text' => $learningStyle['reading_score'],
            'mixed' => 0.5,
        ];

        arsort($scores);
        return array_key_first($scores);
    }

    private function adjustDifficulty(string $baseDifficulty, array $learningStyle, float $similarityScore): string
    {
        if ($similarityScore < 0.4) {
            $levels = ['beginner', 'beginner', 'beginner', 'easy', 'easy', 'medium'];
        } elseif ($similarityScore < 0.7) {
            $levels = ['easy', 'easy', 'medium', 'medium', 'medium', 'hard'];
        } else {
            $levels = ['medium', 'medium', 'hard', 'hard', 'hard', 'expert'];
        }

        $difficultyPreference = $learningStyle['difficulty_preference'];
        
        if ($difficultyPreference === 'easy') {
            return $levels[0];
        } elseif ($difficultyPreference === 'hard') {
            return $levels[count($levels) - 1];
        }

        return $levels[(int) (count($levels) / 2)];
    }

    private function calculateOverallDifficulty(array $modules, array $learningStyle, float $similarityScore): string
    {
        $difficultyScores = array_map(function ($module) {
            return match ($module['difficulty']) {
                'beginner' => 1,
                'easy' => 2,
                'medium' => 3,
                'hard' => 4,
                'expert' => 5,
                default => 3,
            };
        }, $modules);

        $avgScore = array_sum($difficultyScores) / count($difficultyScores);

        if ($avgScore < 2) {
            return 'beginner';
        } elseif ($avgScore < 3) {
            return 'easy';
        } elseif ($avgScore < 4) {
            return 'medium';
        } elseif ($avgScore < 5) {
            return 'hard';
        }

        return 'expert';
    }

    private function predictCompletionProbability(CreateLearningPathDto $dto, array $learningPath, float $similarityScore): float
    {
        $baseProbability = 0.7;
        
        $similarityBonus = $similarityScore * 0.2;
        
        $weeklyHours = $dto->weeklyHours ?? 10;
        $hoursBonus = min($weeklyHours / 20, 0.1);
        
        $moduleCount = count($learningPath['modules']);
        $lengthPenalty = max(0, ($moduleCount - 10) * 0.01);
        
        $difficultyPenalty = match ($learningPath['difficulty_level']) {
            'expert' => 0.1,
            'hard' => 0.05,
            default => 0,
        };

        $probability = $baseProbability + $similarityBonus + $hoursBonus - $lengthPenalty - $difficultyPenalty;

        return max(0.1, min(0.95, $probability));
    }

    private function getRecommendedResources(CreateLearningPathDto $dto, Course $course): array
    {
        $resources = $this->recommendation->getEducationResources(
            userId: $dto->userId,
            courseId: $dto->courseId,
            learningGoal: $dto->learningGoal,
            currentLevel: $dto->currentLevel,
            isCorporate: $dto->isCorporate ?? false,
        );

        return [
            'courses' => $resources['courses'] ?? [],
            'books' => $resources['books'] ?? [],
            'videos' => $resources['videos'] ?? [],
            'practice_exercises' => $resources['practice_exercises'] ?? [],
            'community_forums' => $resources['community_forums'] ?? [],
        ];
    }

    private function generateMilestones(array $modules): array
    {
        $milestones = [];
        $totalModules = count($modules);
        $milestoneCount = min(5, max(3, (int) ceil($totalModules / 3)));

        for ($i = 1; $i <= $milestoneCount; $i++) {
            $moduleIndex = (int) (($totalModules / $milestoneCount) * $i) - 1;
            $moduleIndex = max(0, min($moduleIndex, $totalModules - 1));

            $milestones[] = [
                'milestone_number' => $i,
                'title' => "Milestone {$i}: Complete " . $modules[$moduleIndex]['module_title'],
                'module_index' => $moduleIndex,
                'completion_percentage' => (int) (($i / $milestoneCount) * 100),
                'reward_type' => $i === $milestoneCount ? 'certificate' : 'badge',
            ];
        }

        return $milestones;
    }

    private function saveUserAILearningDesign(CreateLearningPathDto $dto, LearningPathRecommendationDto $recommendation, float $similarityScore): void
    {
        DB::table('user_ai_designs')->insert([
            'user_id' => $dto->userId,
            'tenant_id' => $dto->tenantId,
            'business_group_id' => $dto->businessGroupId,
            'vertical' => 'education',
            'design_type' => 'learning_path',
            'design_data' => json_encode(array_merge($recommendation->toArray(), [
                'similarity_score' => $similarityScore,
                'course_id' => $dto->courseId,
            ])),
            'correlation_id' => $dto->correlationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function adaptLearningPath(int $enrollmentId, array $progressData, string $correlationId): LearningPathRecommendationDto
    {
        $enrollment = Enrollment::with('course.modules')->findOrFail($enrollmentId);
        
        $dto = new CreateLearningPathDto(
            tenantId: $enrollment->tenant_id,
            businessGroupId: $enrollment->corporate_contract_id,
            userId: $enrollment->user_id,
            courseId: $enrollment->course_id,
            correlationId: $correlationId,
            learningGoal: null,
            currentLevel: null,
            targetLevel: null,
            weeklyHours: null,
            preferredTopics: null,
            learningStyle: null,
            isCorporate: $enrollment->corporate_contract_id !== null,
        );

        return DB::transaction(function () use ($dto, $enrollment, $progressData, $correlationId) {
            $currentPath = $enrollment->ai_path ?? [];
            
            $adaptationPrompt = $this->buildAdaptationPrompt($currentPath, $progressData);

            $adaptation = $this->simulateAdaptationAnalysis($adaptationPrompt);

            if ($adaptation['should_adjust'] === true) {
                $updatedPath = $this->applyAdaptations($currentPath, $adaptation);
                
                $enrollment->ai_path = $updatedPath;
                $enrollment->save();

                $this->audit->record('education_learning_path_adapted', 'Enrollment', $enrollmentId, [], [
                    'correlation_id' => $correlationId,
                    'user_id' => $dto->userId,
                    'adaptation' => $adaptation,
                ], $correlationId);

                Log::channel('audit')->info('Learning path adapted', [
                    'correlation_id' => $correlationId,
                    'enrollment_id' => $enrollmentId,
                ]);
            }

            return LearningPathRecommendationDto::fromArray($enrollment->ai_path ?? $currentPath);
        });
    }

    private function buildAdaptationPrompt(array $currentPath, array $progressData): string
    {
        $prompt = "Current learning path:\n" . json_encode($currentPath, JSON_PRETTY_PRINT) . "\n\n";
        $prompt .= "Student progress data:\n" . json_encode($progressData, JSON_PRETTY_PRINT) . "\n\n";
        $prompt .= "Analyze if the student is struggling, ahead, or on track, and suggest specific adjustments.";

        return $prompt;
    }

    private function applyAdaptations(array $currentPath, array $adaptation): array
    {
        $updatedPath = $currentPath;

        if (isset($adaptation['adjustments']) && is_array($adaptation['adjustments'])) {
            foreach ($adaptation['adjustments'] as $moduleId => $adjustmentType) {
                foreach ($updatedPath['modules'] as &$module) {
                    if ($module['module_id'] === $moduleId) {
                        $module['adaptations'][] = $adjustmentType;
                        
                        if ($adaptation['difficulty_change'] === 'increase') {
                            $module['difficulty'] = $this->increaseDifficulty($module['difficulty']);
                        } elseif ($adaptation['difficulty_change'] === 'decrease') {
                            $module['difficulty'] = $this->decreaseDifficulty($module['difficulty']);
                        }
                        
                        break;
                    }
                }
            }
        }

        if (isset($adaptation['additional_resources']) && is_array($adaptation['additional_resources'])) {
            $updatedPath['recommended_resources'] = array_merge(
                $updatedPath['recommended_resources'] ?? [],
                $adaptation['additional_resources']
            );
        }

        $updatedPath['last_adapted_at'] = now()->toIso8601String();

        return $updatedPath;
    }

    private function increaseDifficulty(string $difficulty): string
    {
        $levels = ['beginner', 'easy', 'medium', 'hard', 'expert'];
        $index = array_search($difficulty, $levels);
        
        if ($index !== false && $index < count($levels) - 1) {
            return $levels[$index + 1];
        }

        return $difficulty;
    }

    private function decreaseDifficulty(string $difficulty): string
    {
        $levels = ['beginner', 'easy', 'medium', 'hard', 'expert'];
        $index = array_search($difficulty, $levels);
        
        if ($index !== false && $index > 0) {
            return $levels[$index - 1];
        }

        return $difficulty;
    }

    private function simulateAIAnalysis(string $prompt): array
    {
        return [
            'visual_score' => 0.7,
            'auditory_score' => 0.6,
            'kinesthetic_score' => 0.5,
            'reading_score' => 0.8,
            'preferred_pace' => 'medium',
            'attention_span_minutes' => 45,
            'difficulty_preference' => 'medium',
            'social_learning_score' => 0.6,
        ];
    }

    private function simulateEmbedding(string $text): array
    {
        $embedding = [];
        for ($i = 0; $i < self::EMBEDDING_DIMENSION; $i++) {
            $embedding[] = (mt_rand() / mt_getrandmax() * 2 - 1) * 0.1;
        }
        return $embedding;
    }

    private function simulateAdaptationAnalysis(string $prompt): array
    {
        return [
            'should_adjust' => false,
            'adjustments' => [],
            'difficulty_change' => 'keep',
            'additional_resources' => [],
            'estimated_impact' => 'No adjustment needed',
        ];
    }
}
