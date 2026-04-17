<?php declare(strict_types=1);

namespace App\Models\Cleaning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class CleaningCompany extends Model
{

        protected $table = 'cleaning_companies';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'inn',
            'type', // local, aggregator, premium, industrial
            'rating',
            'settings',
            'tags',
            'is_verified',
            'correlation_id',
        ];

        protected $casts = [
            'settings' => 'json',
            'tags' => 'json',
            'is_verified' => 'boolean',
            'rating' => 'float',
            'tenant_id' => 'integer',
        ];

        /**
         * Boot logic for automatic metadata.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (int) (tenant()->id ?? 0);
                $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
            });

            static::addGlobalScope('tenant', function (Builder $query) {
                if (tenant()) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Services offered by this company.
         */
        public function services(): HasMany
        {
            return $this->hasMany(CleaningService::class);
        }

        /**
         * Orders handled by this company.
         */
        public function orders(): HasMany
        {
            return $this->hasMany(CleaningOrder::class);
        }

        /**
         * Staff schedules and shift allocation.
         */
        public function schedules(): HasMany
        {
            return $this->hasMany(CleaningSchedule::class);
        }

        /**
         * Inventory list (Consumables).
         */
        public function consumables(): HasMany
        {
            return $this->hasMany(CleaningConsumable::class);
        }

        /**
         * Verifies if company is a B2B partner (has INN).
         */
        public function isB2B(): bool
        {
            return !empty($this->inn);
        }
}
