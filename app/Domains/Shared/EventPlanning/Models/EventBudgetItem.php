<?php

declare(strict_types=1);

namespace App\\Domains\\Shared\EventPlanning\Models;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

final class EventBudgetItem extends Model
{
    use TenantScoped;

    protected $table = 'event_planning_budget_items';

    protected $fillable = [
        'uuid',
        'event_id',
        'tenant_id',
        'category',
        'title',
        'estimated_kopecks',
        'actual_kopecks',
        'status',
        'correlation_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'estimated_kopecks' => 'integer',
        'actual_kopecks' => 'integer',
    ];

    /**
     * Relations.
     */
    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly EventDispatcher $eventDispatcher,) {}

    public function $this->eventDispatcher->dispatch(): BelongsTo
    {
        return $this->belongsTo($this->eventDispatcher->class, 'event_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Helpers.
     */
    public function getEstimatedInRublesAttribute(): float
    {
        return $this->estimated_kopecks / 100;
    }

    public function getActualInRublesAttribute(): float
    {
        return $this->actual_kopecks / 100;
    }

    public function getDiffInRublesAttribute(): float
    {
        return ($this->estimated_kopecks - $this->actual_kopecks) / 100;
    }

    public function isPaidInFull(): bool
    {
        return $this->status === 'paid' || $this->actual_kopecks >= $this->estimated_kopecks;
    }

    /**
     * Booted method with Global Scopes.
     */
    protected static function booted(): void
    {
        self::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->tenant_id)) {
                $model->tenant_id = tenant()->id ?? $this->guard->user()?->current_tenant_id ?? 1;
            }
        });

        self::addGlobalScope('tenant', function ($query) {
            if ($tenant = tenant()) {
                $query->where('tenant_id', $tenant->id);
            }
        });
    }
}
