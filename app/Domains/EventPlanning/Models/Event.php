<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Event extends Model
{

    use HasFactory;

    use HasFactory, SoftDeletes;

        protected $table = 'event_planning_events';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'client_id',
            'planner_id',
            'type',
            'title',
            'description',
            'event_date',
            'location',
            'guest_count',
            'status',
            'is_b2b',
            'total_budget_kopecks',
            'prepayment_kopecks',
            'cancellation_fee_kopecks',
            'ai_plan',
            'cancellation_policy',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'uuid' => 'string',
            'event_date' => 'datetime',
            'is_b2b' => 'boolean',
            'total_budget_kopecks' => 'integer',
            'prepayment_kopecks' => 'integer',
            'cancellation_fee_kopecks' => 'integer',
            'ai_plan' => 'array',
            'cancellation_policy' => 'array',
            'tags' => 'json',
        ];

        /**
         * Booted method with Global Scopes.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->tenant_id)) {
                    $model->tenant_id = tenant()->id ?? $this->guard->user()?->current_tenant_id ?? 1;
                }
            });

            static::addGlobalScope('tenant', function ($query) {
                if ($tenant = tenant()) {
                    $query->where('tenant_id', $tenant->id);
                }
            });
        }

        /**
         * Relations.
         */
        public function tenant(): BelongsTo
        {
            return $this->belongsTo(Tenant::class);
        }

        public function client(): BelongsTo
        {
            return $this->belongsTo(User::class, 'client_id');
        }

        public function planner(): BelongsTo
        {
            return $this->belongsTo(User::class, 'planner_id');
        }

        public function vendors(): HasMany
        {
            return $this->hasMany(EventVendor::class, 'event_id');
        }

        public function budgetItems(): HasMany
        {
            return $this->hasMany(EventBudgetItem::class, 'event_id');
        }

        /**
         * Helpers for 2026.
         */
        public function getBudgetInRublesAttribute(): float
        {
            return $this->total_budget_kopecks / 100;
        }

        public function isCompleted(): bool
        {
            return $this->status === 'completed';
        }

        public function isCancelled(): bool
        {
            return $this->status === 'cancelled';
        }

        public function canAddVendor(): bool
        {
            return in_array($this->status, ['draft', 'planning']);
        }
}
