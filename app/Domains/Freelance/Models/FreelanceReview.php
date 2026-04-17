<?php declare(strict_types=1);

namespace App\Domains\Freelance\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FreelanceReview extends Model
{

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

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = (string) Str::uuid();
                $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
            });

            static::addGlobalScope('tenant', function ($builder) {
                $builder->where('tenant_id', tenant()->id ?? 1);
            });
        }

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
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
