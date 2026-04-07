<?php declare(strict_types=1);

namespace App\Models;



use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class BaseDomainModel extends Model
{
    public function __construct(
        private readonly Request $request,
    ) {}

    use HasFactory;

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
                if ($this->guard->check()) {
                    $builder->where('tenant_id', $this->guard->user()->tenant_id);
                }
            });

            // Auto-generate UUID and Correlation ID
            static::creating(function (self $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if ($this->guard->check() && empty($model->tenant_id)) {
                    $model->tenant_id = $this->guard->user()->tenant_id;
                }
                if ($this->request->hasHeader('X-Correlation-ID')) {
                    $model->correlation_id = $this->request->header('X-Correlation-ID');
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
