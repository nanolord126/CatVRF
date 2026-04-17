<?php declare(strict_types=1);

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class InsuranceType extends Model
{

    protected $table = 'insurance_types';

        protected $fillable = [
            'uuid',
            'slug',
            'name',
            'description',
            'base_multipliers',
            'correlation_id',
        ];

        protected $casts = [
            'base_multipliers' => 'json',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

        protected static function booted(): void
        {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

            static::creating(function (self $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
            });
        }

        /**
         * Policies of this type.
         */
        public function policies(): HasMany
        {
            return $this->hasMany(InsurancePolicy::class, 'type_id');
        }

        /**
         * Get base risk factor for a specific category.
         */
        public function getRiskFactor(string $category): float
        {
            return (float) ($this->base_multipliers[$category] ?? 1.0);
        }
}
