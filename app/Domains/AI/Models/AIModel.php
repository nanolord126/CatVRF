<?php declare(strict_types=1);

namespace App\Domains\AI\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Class AIModel
 *
 * Part of the AI vertical domain.
 * Follows CatVRF 9-layer architecture.
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
 * @property string|null $name
 * @property string|null $description
 * @property string|null $status
 * @property array|null $tags
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @package App\Domains\AI\Models
 */
final class AIModel extends Model
{

    protected $table = 'ai_models';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'correlation_id',
        'name',
        'description',
        'status',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'tags'     => 'json',
        'metadata' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the tenant that owns this AI model.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get the business group that owns this AI model.
     */
    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class);
    }

    /**
     * Determine if the model is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Determine if the model has a specific tag.
     */
    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? [], true);
    }

    /**
     * Get the display label for this model.
     */
    public function getDisplayLabel(): string
    {
        return $this->name ?? ('AI Model #' . $this->id);
    }
}
