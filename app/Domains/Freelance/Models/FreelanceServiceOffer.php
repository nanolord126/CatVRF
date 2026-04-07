<?php declare(strict_types=1);

namespace App\Domains\Freelance\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FreelanceServiceOffer extends Model
{
    use HasFactory;

    protected $table = 'freelance_service_offers';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'freelancer_id',
            'title',
            'description',
            'price_kopecks',
            'delivery_days',
            'package_details',
            'is_active',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'price_kopecks' => 'integer',
            'delivery_days' => 'integer',
            'package_details' => 'json',
            'tags' => 'json',
            'is_active' => 'boolean',
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
