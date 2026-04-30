<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Entities\CertificationTestResult as CertificationTestResultEntity;

final class CertificationTestResultModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_certification_test_results';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'trainer_id',
        'uuid',
        'correlation_id',
        'test_name',
        'test_type',
        'certification_id',
        'theory_score',
        'practice_score',
        'total_score',
        'passed',
        'answers',
        'feedback',
        'evaluator_id',
        'evaluated_at',
        'completed_at',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'theory_score' => 'integer',
        'practice_score' => 'integer',
        'total_score' => 'integer',
        'passed' => 'boolean',
        'answers' => 'array',
        'evaluated_at' => 'datetime',
        'completed_at' => 'datetime',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(TrainerModel::class, 'trainer_id');
    }

    public function certification(): BelongsTo
    {
        return $this->belongsTo(TrainerCertificationModel::class, 'certification_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'evaluator_id');
    }

    public function toDomain(): CertificationTestResultEntity
    {
        return new CertificationTestResultEntity(
            id: $this->id,
            tenantId: $this->tenant_id,
            businessGroupId: $this->business_group_id,
            trainerId: $this->trainer_id,
            uuid: $this->uuid,
            correlationId: $this->correlation_id,
            testName: $this->test_name,
            testType: $this->test_type,
            certificationId: $this->certification_id,
            theoryScore: $this->theory_score,
            practiceScore: $this->practice_score,
            totalScore: $this->total_score,
            passed: $this->passed,
            answers: $this->answers,
            feedback: $this->feedback,
            evaluatorId: $this->evaluator_id,
            evaluatedAt: $this->evaluated_at ? \Carbon\CarbonImmutable::parse($this->evaluated_at) : null,
            completedAt: \Carbon\CarbonImmutable::parse($this->completed_at),
            tags: $this->tags,
            metadata: $this->metadata,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(CertificationTestResultEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'tenant_id' => $entity->tenantId,
            'business_group_id' => $entity->businessGroupId,
            'trainer_id' => $entity->trainerId,
            'uuid' => $entity->uuid ?: \Illuminate\Support\Str::uuid(),
            'correlation_id' => $entity->correlationId,
            'test_name' => $entity->testName,
            'test_type' => $entity->testType,
            'certification_id' => $entity->certificationId,
            'theory_score' => $entity->theoryScore,
            'practice_score' => $entity->practiceScore,
            'total_score' => $entity->totalScore,
            'passed' => $entity->passed,
            'answers' => $entity->answers,
            'feedback' => $entity->feedback,
            'evaluator_id' => $entity->evaluatorId,
            'evaluated_at' => $entity->evaluatedAt,
            'completed_at' => $entity->completedAt,
            'tags' => $entity->tags,
            'metadata' => $entity->metadata,
        ]);
    }
}
