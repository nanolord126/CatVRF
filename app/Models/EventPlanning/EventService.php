<?php

declare(strict_types=1);

namespace App\Models\EventPlanning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Class EventService
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
final class EventService extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'event_services';

    protected $fillable = [
        'uuid', 'correlation_id', 'tenant_id', 'planner_id', 'category', 'name', 'description', 'base_price', 'options', 'metadata', 'tags',
    ];

    protected $casts = [
        'options' => 'json',
        'metadata' => 'json',
        'tags' => 'json',
        'base_price' => 'integer', // in kopecks
    ];

    /**
     * Entity Relation with Planner.
     */
    public function planner(): BelongsTo
    {
        return $this->belongsTo(EventPlanner::class, 'planner_id');
    }

    /**
     * Logic: Tenant Scoping + UUID Boot (Canon Rule 2026).
     */
    protected static function booted(): void
    {
        self::creating(function (EventService $model) {
            $model->uuid = (string) Str::uuid();
            $model->correlation_id = (string) Str::uuid();

            if (empty($model->tenant_id)) {
                $model->tenant_id = $this->guard->user()?->tenant_id;
            }
        });

        self::addGlobalScope('tenant', function ($query) {
            if ($this->guard->check()) {
                $query->where('tenant_id', $this->guard->user()?->tenant_id);
            }
        });
    }
}
