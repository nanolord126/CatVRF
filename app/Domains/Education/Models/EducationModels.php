<?php

namespace App\Domains\Education\Models;

use App\Traits\Common\HasEcosystemFeatures;
use App\Traits\Common\HasEcosystemAuth;
use App\Contracts\Common\AIEnableEcosystemEntity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\AuditLog;
use Throwable;

class Course extends Model implements AIEnableEcosystemEntity
{
    use HasEcosystemFeatures, HasEcosystemAuth;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'is_published' => 'boolean',
        'syllabus' => 'array',
        'tags' => 'array',
        'correlation_id' => 'string',
        'tenant_id' => 'integer',
        'student_count' => 'integer',
        'rating' => 'float',
    ];

    public function getAiAdjustedPrice(float $basePrice, array $context = []): float
    {
        $multiplier = 1.0;

        // Повышение цены для популярных курсов
        if (!empty($this->rating) && $this->rating >= 4.5) {
            $multiplier += 0.15; // +15% за высокий рейтинг
        }

        // Скидка для новых курсов
        if ($this->created_at && $this->created_at->diffInDays(Carbon::now()) < 30) {
            $multiplier -= 0.10; // -10% для привлечения студентов
        }

        // Корректировка по спросу (количество студентов)
        if (!empty($this->student_count) && $this->student_count > 100) {
            $multiplier += 0.05; // +5% если много студентов
        }

        return round($basePrice * $multiplier, 2);
    }

    public function getTrustScore(): int
    {
        $score = 60; // базовый скор

        if (!empty($this->rating)) {
            $score = (int) (40 + ($this->rating / 5.0) * 60); // max 100
        }

        if (!empty($this->student_count) && $this->student_count > 50) {
            $score = min(100, $score + 10);
        }

        return $score;
    }

    public function generateAiChecklist(): array
    {
        $checklist = [
            'Review syllabus' => !empty($this->syllabus),
            'Verify teacher credentials' => !empty($this->teacher_id),
            'Check course rating' => !empty($this->rating),
            'Validate lesson content' => $this->lessons()->count() > 0,
            'Review student feedback' => !empty($this->student_count),
        ];

        return array_keys(array_filter($checklist, fn($v) => !$v));
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'teacher_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'course_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'course_id');
    }

    protected static function booted()
    {
        static::creating(function (self $course) {
            $course->correlation_id = $course->correlation_id ?? Str::uuid();
            $course->tenant_id = $course->tenant_id ?? Auth::guard('tenant')?->id();
        });

        static::created(function (self $course) {
            try {
                AuditLog::create([
                    'entity_type' => self::class,
                    'entity_id' => $course->id,
                    'action' => 'created',
                    'user_id' => Auth::id(),
                    'tenant_id' => $course->tenant_id,
                    'correlation_id' => $course->correlation_id,
                    'changes' => [],
                    'metadata' => [
                        'teacher_id' => $course->teacher_id,
                        'price' => $course->price,
                        'is_published' => $course->is_published ?? false,
                    ],
                ]);

                Log::channel('education')->info('Course created', [
                    'course_id' => $course->id,
                    'teacher_id' => $course->teacher_id,
                    'correlation_id' => $course->correlation_id,
                ]);
            } catch (Throwable $e) {
                Log::error('Course creation audit failed', ['course_id' => $course->id, 'error' => $e->getMessage()]);
                \Sentry\captureException($e);
            }
        });

        static::updated(function (self $course) {
            try {
                AuditLog::create([
                    'entity_type' => self::class,
                    'entity_id' => $course->id,
                    'action' => 'updated',
                    'user_id' => Auth::id(),
                    'tenant_id' => $course->tenant_id,
                    'correlation_id' => $course->correlation_id ?? Str::uuid(),
                    'changes' => $course->getChanges(),
                    'metadata' => [
                        'is_published' => $course->is_published,
                        'student_count' => $course->student_count,
                        'rating' => $course->rating,
                    ],
                ]);

                Log::channel('education')->info('Course updated', [
                    'course_id' => $course->id,
                    'correlation_id' => $course->correlation_id,
                ]);
            } catch (Throwable $e) {
                Log::error('Course update audit failed', ['course_id' => $course->id, 'error' => $e->getMessage()]);
            }
        });

        static::deleted(function (self $course) {
            try {
                AuditLog::create([
                    'entity_type' => self::class,
                    'entity_id' => $course->id,
                    'action' => 'deleted',
                    'user_id' => Auth::id(),
                    'tenant_id' => $course->tenant_id,
                    'correlation_id' => $course->correlation_id ?? Str::uuid(),
                    'changes' => [],
                    'metadata' => [
                        'teacher_id' => $course->teacher_id,
                    ],
                ]);

                Log::channel('education')->info('Course deleted', [
                    'course_id' => $course->id,
                    'correlation_id' => $course->correlation_id,
                ]);
            } catch (Throwable $e) {
                Log::error('Course deletion audit failed', ['course_id' => $course->id, 'error' => $e->getMessage()]);
            }
        });
    }
}

class Lesson extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'duration_minutes' => 'integer',
        'resources' => 'array',
        'is_free' => 'boolean',
        'correlation_id' => 'string',
        'tenant_id' => 'integer',
        'sequence_number' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    protected static function booted()
    {
        static::creating(function (self $lesson) {
            $lesson->correlation_id = $lesson->correlation_id ?? Str::uuid();
            $lesson->tenant_id = $lesson->tenant_id ?? Auth::guard('tenant')?->id();
            if (empty($lesson->sequence_number)) {
                $lesson->sequence_number = 0;
            }
        });

        static::created(function (self $lesson) {
            try {
                AuditLog::create([
                    'entity_type' => self::class,
                    'entity_id' => $lesson->id,
                    'action' => 'created',
                    'user_id' => Auth::id(),
                    'tenant_id' => $lesson->tenant_id,
                    'correlation_id' => $lesson->correlation_id,
                    'changes' => [],
                    'metadata' => [
                        'course_id' => $lesson->course_id,
                        'duration_minutes' => $lesson->duration_minutes,
                        'is_free' => $lesson->is_free,
                    ],
                ]);

                Log::channel('education')->info('Lesson created', [
                    'lesson_id' => $lesson->id,
                    'course_id' => $lesson->course_id,
                    'correlation_id' => $lesson->correlation_id,
                ]);
            } catch (Throwable $e) {
                Log::error('Lesson creation audit failed', ['lesson_id' => $lesson->id, 'error' => $e->getMessage()]);
            }
        });

        static::updated(function (self $lesson) {
            try {
                AuditLog::create([
                    'entity_type' => self::class,
                    'entity_id' => $lesson->id,
                    'action' => 'updated',
                    'user_id' => Auth::id(),
                    'tenant_id' => $lesson->tenant_id,
                    'correlation_id' => $lesson->correlation_id ?? Str::uuid(),
                    'changes' => $lesson->getChanges(),
                    'metadata' => [
                        'duration_minutes' => $lesson->duration_minutes,
                        'is_free' => $lesson->is_free,
                    ],
                ]);

                Log::channel('education')->info('Lesson updated', ['lesson_id' => $lesson->id, 'correlation_id' => $lesson->correlation_id]);
            } catch (Throwable $e) {
                Log::error('Lesson update audit failed', ['lesson_id' => $lesson->id, 'error' => $e->getMessage()]);
            }
        });
    }
}

class Enrollment extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress_percent' => 'integer',
        'is_paid' => 'boolean',
        'correlation_id' => 'string',
        'tenant_id' => 'integer',
        'amount_paid' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    protected static function booted()
    {
        static::creating(function (self $enrollment) {
            $enrollment->correlation_id = $enrollment->correlation_id ?? Str::uuid();
            $enrollment->tenant_id = $enrollment->tenant_id ?? Auth::guard('tenant')?->id();
            $enrollment->enrolled_at = $enrollment->enrolled_at ?? Carbon::now();
            if (empty($enrollment->progress_percent)) {
                $enrollment->progress_percent = 0;
            }
        });

        static::created(function (self $enrollment) {
            try {
                AuditLog::create([
                    'entity_type' => self::class,
                    'entity_id' => $enrollment->id,
                    'action' => 'created',
                    'user_id' => Auth::id(),
                    'tenant_id' => $enrollment->tenant_id,
                    'correlation_id' => $enrollment->correlation_id,
                    'changes' => [],
                    'metadata' => [
                        'student_id' => $enrollment->student_id,
                        'course_id' => $enrollment->course_id,
                        'is_paid' => $enrollment->is_paid,
                        'amount_paid' => $enrollment->amount_paid,
                    ],
                ]);

                Log::channel('education')->info('Student enrolled', [
                    'enrollment_id' => $enrollment->id,
                    'student_id' => $enrollment->student_id,
                    'course_id' => $enrollment->course_id,
                    'correlation_id' => $enrollment->correlation_id,
                ]);
            } catch (Throwable $e) {
                Log::error('Enrollment creation audit failed', ['enrollment_id' => $enrollment->id, 'error' => $e->getMessage()]);
                \Sentry\captureException($e);
            }
        });

        static::updated(function (self $enrollment) {
            try {
                if ($enrollment->isDirty('progress_percent') && $enrollment->progress_percent >= 100 && !$enrollment->completed_at) {
                    $enrollment->completed_at = Carbon::now();
                }

                AuditLog::create([
                    'entity_type' => self::class,
                    'entity_id' => $enrollment->id,
                    'action' => 'updated',
                    'user_id' => Auth::id(),
                    'tenant_id' => $enrollment->tenant_id,
                    'correlation_id' => $enrollment->correlation_id,
                    'changes' => $enrollment->getChanges(),
                    'metadata' => [
                        'progress_percent' => $enrollment->progress_percent,
                        'is_paid' => $enrollment->is_paid,
                        'completed_at' => $enrollment->completed_at,
                    ],
                ]);

                if ($enrollment->isDirty('progress_percent')) {
                    Log::channel('education')->info('Enrollment progress updated', [
                        'enrollment_id' => $enrollment->id,
                        'progress_percent' => $enrollment->progress_percent,
                        'correlation_id' => $enrollment->correlation_id,
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('Enrollment update audit failed', ['enrollment_id' => $enrollment->id, 'error' => $e->getMessage()]);
            }
        });
    }
}

