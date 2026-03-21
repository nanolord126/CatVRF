<?php declare(strict_types=1);

namespace App\Domains\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

final class InstructorEarning extends Model
{
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
