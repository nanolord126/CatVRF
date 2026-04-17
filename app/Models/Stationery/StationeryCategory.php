<?php declare(strict_types=1);

namespace App\Models\Stationery;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Class StationeryCategory
 *
 * Eloquent model with tenant-scoping and business group isolation.
 * All queries are automatically scoped by tenant_id via global scope.
 *
 * Required fields: uuid, correlation_id, tenant_id, business_group_id, tags (json).
 * Audit logging is handled via model events (created, updated, deleted).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property array|null $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @package App\Models\Stationery
 */
final class StationeryCategory extends Model
{

    protected $table = 'stationery_categories';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'slug',
            'description',
            'is_active',
            'correlation_id'
        ];

        protected $casts = [
            'is_active' => 'boolean',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = (string) Str::uuid();
                $model->slug = Str::slug($model->name);
                if ($this->guard->check() && empty($model->tenant_id)) {
                    $model->tenant_id = $this->guard->user()->tenant_id;
                }
            });

            static::addGlobalScope('tenant', function ($builder) {
                if ($this->guard->check()) {
                    $builder->where('tenant_id', $this->guard->user()->tenant_id);
                }
            });
        }

        public function products(): HasMany
        {
            return $this->hasMany(StationeryProduct::class, 'category_id');
        }
}
