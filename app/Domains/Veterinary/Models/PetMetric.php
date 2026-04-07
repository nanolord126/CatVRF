<?php declare(strict_types=1);

namespace App\Domains\Veterinary\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

final class PetMetric extends Model
{

    use HasFactory;

    protected $table = 'pet_metrics';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'pet_id',
            'metric_type',
            'value',
            'unit',
            'measured_at',
            'source',
            'correlation_id',
        ];

        protected $casts = [
            'value' => 'float',
            'measured_at' => 'datetime',
        ];

        protected static function booted(): void
        {
            static::creating(function (PetMetric $model) {
                $model->uuid = (string) Str::uuid();
                if (function_exists('tenant') && tenant() && !$model->tenant_id) {
                    $model->tenant_id = tenant()->id;
                }
            });

            static::addGlobalScope('tenant_id', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        public function pet(): BelongsTo
        {
            return $this->belongsTo(Pet::class);
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
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
