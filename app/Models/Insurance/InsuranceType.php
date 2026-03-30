<?php declare(strict_types=1);

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InsuranceType extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
