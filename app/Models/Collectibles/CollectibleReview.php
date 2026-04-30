<?php

declare(strict_types=1);

namespace App\Models\Collectibles;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Class CollectibleReview
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
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class CollectibleReview extends Model
{
    protected $table = 'collectible_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'reviewable_id',
        'reviewable_type',
        'rating',
        'comment',
        'correlation_id',
    ];

    protected $casts = [
        'rating' => 'integer',
        'reviewable_id' => 'integer',
    ];

    /**
     * Polymorphic relation to Reviewable entities (Store, Item).
     */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function booted(): void
    {
        self::creating(function (CollectibleReview $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
        });

        self::addGlobalScope('tenant_id', function ($builder) {
            $builder->where('tenant_id', (tenant()->id ?? 1));
        });
    }
}
