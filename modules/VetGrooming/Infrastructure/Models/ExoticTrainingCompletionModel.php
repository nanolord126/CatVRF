<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VetGrooming\Domain\Entities\ExoticTrainingCompletion;

final class ExoticTrainingCompletionModel extends Model
{
    protected $table = 'exotic_training_completions';

    protected $fillable = [
        'master_id',
        'course_id',
        'tenant_id',
        'score',
        'passed',
        'certificate_file',
        'practical_exam_video',
        'exam_results',
        'instructor_notes',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'passed' => 'boolean',
        'exam_results' => 'array',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Master::class, 'master_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(ExoticTrainingCourseModel::class, 'course_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    public function toDomain(): ExoticTrainingCompletion
    {
        return new ExoticTrainingCompletion(
            id: $this->id,
            masterId: $this->master_id,
            courseId: $this->course_id,
            tenantId: $this->tenant_id,
            score: $this->score,
            passed: $this->passed,
            certificateFile: $this->certificate_file,
            practicalExamVideo: $this->practical_exam_video,
            examResults: $this->exam_results,
            instructorNotes: $this->instructor_notes,
            completedAt: \Carbon\CarbonImmutable::parse($this->completed_at),
            expiresAt: $this->expires_at ? \Carbon\CarbonImmutable::parse($this->expires_at) : null,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(ExoticTrainingCompletion $completion): self
    {
        return new self([
            'id' => $completion->id,
            'master_id' => $completion->masterId,
            'course_id' => $completion->courseId,
            'tenant_id' => $completion->tenantId,
            'score' => $completion->score,
            'passed' => $completion->passed,
            'certificate_file' => $completion->certificateFile,
            'practical_exam_video' => $completion->practicalExamVideo,
            'exam_results' => $completion->examResults,
            'instructor_notes' => $completion->instructorNotes,
            'completed_at' => $completion->completedAt,
            'expires_at' => $completion->expiresAt,
            'created_at' => $completion->createdAt,
            'updated_at' => $completion->updatedAt,
        ]);
    }
}
