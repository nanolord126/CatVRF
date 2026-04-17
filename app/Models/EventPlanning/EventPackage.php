<?php declare(strict_types=1);

namespace App\Models\EventPlanning;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class EventPackage extends Model
{

        protected $table = 'event_packages';

        protected $fillable = [
            'uuid', 'correlation_id', 'tenant_id', 'planner_id', 'name', 'total_price', 'discount_percent', 'service_ids', 'is_b2b_only',
        ];

        protected $casts = [
            'service_ids' => 'json',
            'is_b2b_only' => 'boolean',
            'total_price' => 'integer',
            'discount_percent' => 'integer',
        ];

        /**
         * Boot Logic.
         */
        protected static function booted(): void
        {
            static::creating(function (EventPackage $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->correlation_id)) {
                    $model->correlation_id = (string) Str::uuid();
                }

                if (empty($model->tenant_id)) {
                    $model->tenant_id = $this->guard->user()?->tenant_id;
                }
            });

            static::addGlobalScope('tenant', function ($query) {
                if ($this->guard->check()) {
                    $query->where('tenant_id', $this->guard->user()?->tenant_id);
                }
            });
        }

        /**
         * Planner associated.
         */
        public function planner(): BelongsTo
        {
            return $this->belongsTo(EventPlanner::class, 'planner_id');
        }
}
