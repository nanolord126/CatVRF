<?php

namespace App\Domains\Education\Models;

use App\Traits\Common\HasEcosystemFeatures;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\AuditLog;
use Throwable;

class Quiz extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'questions' => 'array',
        'is_active' => 'boolean',
        'correlation_id' => 'string',
        'tenant_id' => 'integer',
        'passing_score' => 'float',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    protected static function booted()
    {
        static::creating(function (self $quiz) {
            $quiz->correlation_id = $quiz->correlation_id ?? Str::uuid();
            $quiz->tenant_id = $quiz->tenant_id ?? Auth::guard('tenant')?->id();
            if (empty($quiz->passing_score)) {
                $quiz->passing_score = 70.0;
            }
        });

        static::created(function (self $quiz) {
            try {
                AuditLog::create([
                    'entity_type' => self::class,
                    'entity_id' => $quiz->id,
                    'action' => 'created',
                    'user_id' => Auth::id(),
                    'tenant_id' => $quiz->tenant_id,
                    'correlation_id' => $quiz->correlation_id,
                    'changes' => [],
                    'metadata' => [
                        'lesson_id' => $quiz->lesson_id,
                        'question_count' => count($quiz->questions ?? []),
                        'passing_score' => $quiz->passing_score,
                    ],
                ]);

                Log::channel('education')->info('Quiz created', [
                    'quiz_id' => $quiz->id,
                    'correlation_id' => $quiz->correlation_id,
                    'user_id' => Auth::id(),
                ]);
            } catch (Throwable $e) {
                Log::error('Quiz creation audit failed', [
                    'quiz_id' => $quiz->id,
                    'error' => $e->getMessage(),
                ]);
                \Sentry\captureException($e);
            }
        });

        static::updated(function (self $quiz) {
            try {
                AuditLog::create([
                    'entity_type' => self::class,
                    'entity_id' => $quiz->id,
                    'action' => 'updated',
                    'user_id' => Auth::id(),
                    'tenant_id' => $quiz->tenant_id,
                    'correlation_id' => $quiz->correlation_id ?? Str::uuid(),
                    'changes' => $quiz->getChanges(),
                    'metadata' => [
                        'is_active' => $quiz->is_active,
                    ],
                ]);

                Log::channel('education')->info('Quiz updated', [
                    'quiz_id' => $quiz->id,
                    'correlation_id' => $quiz->correlation_id,
                    'user_id' => Auth::id(),
                ]);
            } catch (Throwable $e) {
                Log::error('Quiz update audit failed', ['quiz_id' => $quiz->id, 'error' => $e->getMessage()]);
            }
        });
    }
}

class QuizAttempt extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'score' => 'float',
        'is_passed' => 'boolean',
        'answers' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'correlation_id' => 'string',
        'tenant_id' => 'integer',
        'duration_seconds' => 'integer',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    protected static function booted()
    {
        static::creating(function (self $attempt) {
            $attempt->correlation_id = $attempt->correlation_id ?? Str::uuid();
            $attempt->tenant_id = $attempt->tenant_id ?? Auth::guard('tenant')?->id();
            $attempt->started_at = $attempt->started_at ?? now();
        });

        static::created(function (self $attempt) {
            try {
                AuditLog::create([
                    'entity_type' => self::class,
                    'entity_id' => $attempt->id,
                    'action' => 'created',
                    'user_id' => Auth::id(),
                    'tenant_id' => $attempt->tenant_id,
                    'correlation_id' => $attempt->correlation_id,
                    'changes' => [],
                    'metadata' => [
                        'quiz_id' => $attempt->quiz_id,
                        'user_id' => $attempt->user_id,
                        'status' => 'started',
                    ],
                ]);

                Log::channel('education')->info('QuizAttempt started', [
                    'attempt_id' => $attempt->id,
                    'quiz_id' => $attempt->quiz_id,
                    'user_id' => $attempt->user_id,
                    'correlation_id' => $attempt->correlation_id,
                ]);
            } catch (Throwable $e) {
                Log::error('QuizAttempt creation audit failed', ['attempt_id' => $attempt->id, 'error' => $e->getMessage()]);
            }
        });

        static::updated(function (self $attempt) {
            try {
                if ($attempt->isDirty('finished_at') && $attempt->finished_at) {
                    $durationSeconds = $attempt->started_at->diffInSeconds($attempt->finished_at);
                    $attempt->duration_seconds = $durationSeconds;
                }

                AuditLog::create([
                    'entity_type' => self::class,
                    'entity_id' => $attempt->id,
                    'action' => 'completed',
                    'user_id' => Auth::id(),
                    'tenant_id' => $attempt->tenant_id,
                    'correlation_id' => $attempt->correlation_id,
                    'changes' => $attempt->getChanges(),
                    'metadata' => [
                        'quiz_id' => $attempt->quiz_id,
                        'score' => $attempt->score,
                        'is_passed' => $attempt->is_passed,
                        'duration_seconds' => $attempt->duration_seconds,
                    ],
                ]);

                Log::channel('education')->info('QuizAttempt completed', [
                    'attempt_id' => $attempt->id,
                    'quiz_id' => $attempt->quiz_id,
                    'score' => $attempt->score,
                    'is_passed' => $attempt->is_passed,
                    'correlation_id' => $attempt->correlation_id,
                ]);
            } catch (Throwable $e) {
                Log::error('QuizAttempt update audit failed', ['attempt_id' => $attempt->id, 'error' => $e->getMessage()]);
            }
        });
    }
}

class Certification extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
        'correlation_id' => 'string',
        'tenant_id' => 'integer',
        'final_score' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    protected static function booted()
    {
        static::creating(function (self $cert) {
            $cert->correlation_id = $cert->correlation_id ?? Str::uuid();
            $cert->tenant_id = $cert->tenant_id ?? Auth::guard('tenant')?->id();
            $cert->issued_at = $cert->issued_at ?? now();
            if (empty($cert->expires_at)) {
                $cert->expires_at = now()->addYears(2);
            }
        });

        static::created(function (self $cert) {
            try {
                AuditLog::create([
                    'entity_type' => self::class,
                    'entity_id' => $cert->id,
                    'action' => 'issued',
                    'user_id' => Auth::id(),
                    'tenant_id' => $cert->tenant_id,
                    'correlation_id' => $cert->correlation_id,
                    'changes' => [],
                    'metadata' => [
                        'user_id' => $cert->user_id,
                        'course_id' => $cert->course_id,
                        'final_score' => $cert->final_score,
                        'certificate_number' => $cert->certificate_number ?? 'N/A',
                    ],
                ]);

                Log::channel('education')->info('Certification issued', [
                    'certification_id' => $cert->id,
                    'user_id' => $cert->user_id,
                    'course_id' => $cert->course_id,
                    'correlation_id' => $cert->correlation_id,
                ]);
            } catch (Throwable $e) {
                Log::error('Certification creation audit failed', ['cert_id' => $cert->id, 'error' => $e->getMessage()]);
            }
        });
    }
}
