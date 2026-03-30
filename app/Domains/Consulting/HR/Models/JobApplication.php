<?php declare(strict_types=1);

namespace App\Domains\Consulting\HR\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class JobApplication extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, BelongsToTenant, SoftDeletes;

        protected $table = 'hr_applications';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'vacancy_id',
            'user_id',      // Кандидат
            'resume_url',
            'cover_letter',
            'status',       // pending, review, interview, rejected, hired
            'interview_at', // nullable datetime
            'notes',        // HR notes (internal)
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'interview_at' => 'datetime',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->correlation_id = $model->correlation_id ?? request()->header('X-Correlation-ID', (string) Str::uuid());
                $model->status = $model->status ?? 'pending';
            });
        }

        public function vacancy(): BelongsTo
        {
            return $this->belongsTo(JobVacancy::class, 'vacancy_id');
        }

        public function candidate(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'user_id');
        }
}
