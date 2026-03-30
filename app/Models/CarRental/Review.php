<?php declare(strict_types=1);

namespace App\Models\CarRental;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Review extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'car_reviews';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'booking_id',
            'car_id',
            'rating',
            'comment',
            'media',
            'correlation_id',
        ];

        /**
         * Casting logic for nested JSON structures.
         */
        protected $casts = [
            'rating' => 'integer',
            'media' => 'json',
            'uuid' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

        /**
         * Boot logic for tenant-aware scoping.
         */
        protected static function booted(): void
        {
            // 1. Force Tenant Scoping via global scope
            static::addGlobalScope('tenant', function (Builder $builder) {
                $tenantId = tenant()->id ?? config('multitenancy.default_tenant_id');
                if ($tenantId) {
                    $builder->where('car_reviews.tenant_id', $tenantId);
                }
            });

            // 2. Automatic UUID generation and correlation assignment
            static::creating(function (Model $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->correlation_id)) {
                    $model->correlation_id = (string) Str::uuid();
                }
                if (empty($model->tenant_id)) {
                    $model->tenant_id = tenant()->id ?? 1;
                }
            });
        }

        /**
         * Relationship: Associated User (Client).
         */
        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'user_id');
        }

        /**
         * Relationship: Originating Booking transaction.
         */
        public function booking(): BelongsTo
        {
            return $this->belongsTo(Booking::class, 'booking_id');
        }

        /**
         * Relationship: The vehicle being reviewed.
         */
        public function car(): BelongsTo
        {
            return $this->belongsTo(Car::class, 'car_id');
        }

        /**
         * Helper to retrieve formatted rating star icons/labels.
         */
        public function getRatingLabelAttribute(): string
        {
            return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
        }

        /**
         * Check if review includes evidence (photos).
         */
        public function hasPhotos(): bool
        {
            return !empty($this->media) && count($this->media) > 0;
        }

        /**
         * Correlation Tracking implementation.
         */
        public function getActiveTraceId(): string
        {
            return (string) ($this->correlation_id ?? 'root-trace-id');
        }

        /**
         * Scope for filtering by high/low ratings.
         */
        public function scopeIsPositive(Builder $query): Builder
        {
            return $query->where('rating', '>=', 4);
        }
}
