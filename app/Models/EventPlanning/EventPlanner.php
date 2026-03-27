<?php

declare(strict_types=1);

namespace App\Models\EventPlanning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * EventPlanner Model (Company/Freelancer).
 * Implementation: Layer 2 (Domain/Database Layer).
 * Represents a business entity (Tenant) that organizes events.
 */
final class EventPlanner extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'event_planners';

    /**
     * Mass assignment fields.
     */
    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'name',
        'inn',
        'type',
        'rating',
        'specializations',
        'metadata',
        'tags',
        'is_active',
    ];

    /**
     * Type casting.
     */
    protected $casts = [
        'metadata' => 'json',
        'tags' => 'json',
        'specializations' => 'json',
        'rating' => 'float',
        'is_active' => 'boolean',
    ];

    /**
     * Logic: Tenant Scoping + UUID Boot (Canon Rule 2026).
     */
    protected static function booted(): void
    {
        static::creating(function (EventPlanner $model) {
            $model->uuid = (string) Str::uuid();
            $model->correlation_id = (string) Str::uuid();

            if (empty($model->tenant_id)) {
                $model->tenant_id = auth()->user()?->tenant_id 
                    ?? (function_exists('tenant') ? tenant()?->id : 1); // Fallback to 1 for local
            }
        });

        // Global Tenant Scoping
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', auth()->user()?->tenant_id);
            }
        });
    }

    /**
     * Relations with Services.
     */
    public function services(): HasMany
    {
        return $this->hasMany(EventService::class, 'planner_id');
    }

    /**
     * Relations with Venues.
     */
    public function venues(): HasMany
    {
        return $this->hasMany(EventVenue::class, 'planner_id');
    }

    /**
     * Relations with Projects.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(EventProject::class, 'planner_id');
    }

    /**
     * Relations with Reviews.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(EventReview::class, 'planner_id');
    }
}
