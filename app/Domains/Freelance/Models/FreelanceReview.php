<?php

declare(strict_types=1);

namespace App\Domains\Freelance\Models;

use Carbon\CarbonImmutable;

use App\Traits\TenantScoped;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class FreelanceReview extends Model
{
    use TenantScoped;

    protected $table = 'freelance_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'order_id',
        'reviewer_id',
        'freelancer_id',
        'rating',
        'comment',
        'metrics',
        'correlation_id',
    ];

    protected $casts = [
        'rating' => 'integer',
        'metrics' => 'json',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(FreelanceOrder::class);
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(Freelancer::class);
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected static function booted(): void
    {
        self::creating(function (self $model) {
            $model->uuid = (string) Str::uuid();
            $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
        });

        self::addGlobalScope('tenant', function ($builder) {
            $builder->where('tenant_id', tenant()->id ?? 1);
        });
    }
}
