<?php

declare(strict_types=1);

namespace App\Models\EventPlanning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * EventService Model (Decor, Host, Sound, etc.).
 * Implementation: Layer 2 (Domain/Database Layer).
 */
final class EventService extends Model
{
    use HasFactory, SoftDeletes;

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
     * Logic: Tenant Scoping + UUID Boot (Canon Rule 2026).
     */
    protected static function booted(): void
    {
        static::creating(function (EventService $model) {
            $model->uuid = (string) Str::uuid();
            $model->correlation_id = (string) Str::uuid();

            if (empty($model->tenant_id)) {
                $model->tenant_id = auth()->user()?->tenant_id;
            }
        });

        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', auth()->user()?->tenant_id);
            }
        });
    }

    /**
     * Entity Relation with Planner.
     */
    public function planner(): BelongsTo
    {
        return $this->belongsTo(EventPlanner::class, 'planner_id');
    }
}
