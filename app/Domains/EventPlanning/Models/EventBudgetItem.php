<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EventBudgetItem extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;

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
         * Booted method with Global Scopes.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->tenant_id)) {
                    $model->tenant_id = tenant()->id ?? auth()->user()?->current_tenant_id ?? 1;
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
        public function event(): BelongsTo
        {
            return $this->belongsTo(Event::class, 'event_id');
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
}
