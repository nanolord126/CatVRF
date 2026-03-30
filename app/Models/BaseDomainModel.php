<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BaseDomainModel extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * @var string[]
         */
        protected $guarded = ['id', 'uuid', 'tenant_id'];

        /**
         * Boot the model.
         */
        protected static function booted(): void
        {
            // Enforce Tenant Isolation globally
            static::addGlobalScope('tenant', function (Builder $builder) {
                if (auth()->check()) {
                    $builder->where('tenant_id', auth()->user()->tenant_id);
                }
            });

            // Auto-generate UUID and Correlation ID
            static::creating(function (Model $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (auth()->check() && empty($model->tenant_id)) {
                    $model->tenant_id = auth()->user()->tenant_id;
                }
                if (request()->hasHeader('X-Correlation-ID')) {
                    $model->correlation_id = request()->header('X-Correlation-ID');
                } elseif (empty($model->correlation_id)) {
                    $model->correlation_id = (string) Str::uuid();
                }
            });
        }

        /**
         * Scope for Business Group (Sub-tenancy) if active
         */
        public function scopeInBusinessGroup(Builder $query, int $businessGroupId): Builder
        {
            return $query->where('business_group_id', $businessGroupId);
        }
}
