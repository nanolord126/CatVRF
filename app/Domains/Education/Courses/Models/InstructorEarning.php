<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InstructorEarning extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes;

        protected $fillable = [
            'tenant_id',
            'instructor_id',
            'course_id',
            'total_students',
            'total_revenue',
            'platform_commission',
            'instructor_earnings',
            'last_payout_at',
            'next_payout_at',
            'correlation_id',
        ];

        protected $casts = [
            'last_payout_at' => 'datetime',
            'next_payout_at' => 'datetime',
        ];

        public function booted(): void
        {
            static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant('id') ?? 0));
        }

        public function course(): BelongsTo
        {
            return $this->belongsTo(Course::class);
        }
}
