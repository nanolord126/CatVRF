<?php declare(strict_types=1);

namespace App\Domains\Logistics\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CourierRating extends Model
{

    use HasFactory;

    use HasFactory, SoftDeletes;

        protected $table = 'courier_ratings';

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'courier_service_id',
            'reviewer_id',
            'rating',
            'comment',
            'media',
            'verified_transaction',
            'correlation_id',
        ];

        protected $casts = [
            'media' => 'collection',
            'verified_transaction' => 'boolean',
        ];

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                if (function_exists('tenant') && tenant()) {
                    $query->where('tenant_id', tenant()?->id);
                }
            });
        }

        public function courierService(): BelongsTo
        {
            return $this->belongsTo(CourierService::class);
        }

        public function reviewer(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class);
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
