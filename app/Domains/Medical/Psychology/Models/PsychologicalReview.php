<?php declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PsychologicalReview extends Model
{

    use HasFactory;

    protected $table = 'psy_reviews';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'psychologist_id',
            'rating',
            'comment',
            'is_public',
            'correlation_id',
        ];

        protected $casts = [
            'rating' => 'integer',
            'is_public' => 'boolean',
        ];

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });

            static::creating(function (self $model) {
                $model->uuid = (string) Str::uuid();
                $model->correlation_id = (string) Str::uuid();
                $model->tenant_id = tenant()->id ?? 0;
            });
        }

        public function psychologist(): BelongsTo
        {
            return $this->belongsTo(Psychologist::class, 'psychologist_id');
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
