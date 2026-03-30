<?php declare(strict_types=1);

namespace App\Models\CarRental;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RentalCompany extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'rental_companies';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'inn',
            'is_verified',
            'rating',
            'settings',
            'tags',
            'correlation_id',
        ];

        /**
         * Casting logic for nested JSON structures.
         */
        protected $casts = [
            'is_verified' => 'boolean',
            'rating' => 'float',
            'settings' => 'json',
            'tags' => 'json',
            'uuid' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

        /**
         * Boot logic: Ensuring standard tenant scoping and unique identifiers.
         */
        protected static function booted(): void
        {
            // 1. Force Tenant Scoping via global scope
            static::addGlobalScope('tenant', function (Builder $builder) {
                $tenantId = tenant()->id ?? config('multitenancy.default_tenant_id');
                if ($tenantId) {
                    $builder->where('tenant_id', $tenantId);
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
         * Relationship: Ownership of the vehicle fleet.
         */
        public function cars(): HasMany
        {
            return $this->hasMany(Car::class, 'rental_company_id');
        }

        /**
         * Helper to retrieve verified status for logic branching.
         */
        public function isPremium(): bool
        {
            return $this->is_verified && $this->rating >= 4.5;
        }

        /**
         * Scope for searching by brand/legal name.
         */
        public function scopeSearch(Builder $query, string $term): Builder
        {
            return $query->where('name', 'LIKE', "%{$term}%")
                         ->orWhere('inn', 'LIKE', "%{$term}%");
        }

        /**
         * Correlation Tracking implementation.
         */
        public function getActiveTraceId(): string
        {
            return (string) ($this->correlation_id ?? 'root-trace-id');
        }

        /**
         * Retrieve standard commission percentage from settings.
         */
        public function getCommission(): int
        {
            return (int) ($this->settings['commission_percent'] ?? 14);
        }
}
