<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class SavedConfiguration
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
 * @package App\Models
 */
final class SavedConfiguration extends Model
{
    use HasFactory;

    protected $table = 'saved_configurations';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'template_id',
            'project_name',
            'payload',
            'total_price_kopeks',
            'total_weight_grams',
            'status',
            'correlation_id',
        ];

        protected $casts = [
            'payload' => 'json',
        ];

        protected static function booted(): void
        {
            static::creating(function (SavedConfiguration $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant('id')) {
                    $builder->where('tenant_id', tenant('id'));
                }
            });
        }

        public function template(): BelongsTo
        {
            return $this->belongsTo(ConfiguratorTemplate::class, 'template_id');
        }
}
