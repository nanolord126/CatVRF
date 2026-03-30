<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Certificate extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes;

        protected $fillable = [
            'tenant_id',
            'enrollment_id',
            'course_id',
            'student_id',
            'certificate_number',
            'issued_at',
            'certificate_url',
            'verification_code',
            'student_name',
            'achievement_description',
            'correlation_id',
        ];

        protected $casts = [
            'issued_at' => 'datetime',
        ];

        public function booted(): void
        {
            static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant('id') ?? 0));
        }

        public function enrollment(): BelongsTo
        {
            return $this->belongsTo(Enrollment::class);
        }

        public function course(): BelongsTo
        {
            return $this->belongsTo(Course::class);
        }
}
