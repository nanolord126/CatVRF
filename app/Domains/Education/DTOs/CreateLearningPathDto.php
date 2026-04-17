<?php declare(strict_types=1);

namespace App\Domains\Education\DTOs;

final readonly class CreateLearningPathDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public int $userId,
        public int $courseId,
        public string $correlationId,
        public ?string $idempotencyKey = null,
        public ?string $learningGoal = null,
        public ?string $currentLevel = null,
        public ?string $targetLevel = null,
        public ?int $weeklyHours = null,
        public ?array $preferredTopics = null,
        public ?string $learningStyle = null,
        public ?bool $isCorporate = null,
    ) {}

    public static function from(\Illuminate\Http\Request $request): self
    {
        $isB2B = $request->has('inn') && $request->has('business_card_id');

        return new self(
            tenantId: (int) tenant()->id,
            businessGroupId: $isB2B ? (int) $request->input('business_card_id') : null,
            userId: (int) $request->input('user_id'),
            courseId: (int) $request->input('course_id'),
            correlationId: $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid(),
            idempotencyKey: $request->header('X-Idempotency-Key'),
            learningGoal: $request->input('learning_goal'),
            currentLevel: $request->input('current_level'),
            targetLevel: $request->input('target_level'),
            weeklyHours: $request->input('weekly_hours') ? (int) $request->input('weekly_hours') : null,
            preferredTopics: $request->input('preferred_topics'),
            learningStyle: $request->input('learning_style'),
            isCorporate: $isB2B,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'user_id' => $this->userId,
            'course_id' => $this->courseId,
            'correlation_id' => $this->correlationId,
            'learning_goal' => $this->learningGoal,
            'current_level' => $this->currentLevel,
            'target_level' => $this->targetLevel,
            'weekly_hours' => $this->weeklyHours,
            'preferred_topics' => $this->preferredTopics,
            'learning_style' => $this->learningStyle,
            'is_corporate' => $this->isCorporate,
        ];
    }

    public function toCacheKey(): string
    {
        return sprintf(
            'learning_path:%d:%d:%d:%s',
            $this->tenantId,
            $this->userId,
            $this->courseId,
            md5(serialize([
                $this->learningGoal,
                $this->currentLevel,
                $this->targetLevel,
                $this->weeklyHours,
                $this->preferredTopics,
                $this->learningStyle,
            ]))
        );
    }
}
