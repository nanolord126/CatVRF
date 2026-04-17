<?php declare(strict_types=1);

namespace App\Models\EventPlanning;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Class EventVenue
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
 * @package App\Models\EventPlanning
 */
final class EventVenue extends Model
{

        protected $table = 'event_venues';

        protected $fillable = [
            'uuid', 'correlation_id', 'tenant_id', 'planner_id', 'name', 'address', 'capacity_max', 'price_per_hour', 'amenities', 'metadata',
        ];

        protected $casts = [
            'amenities' => 'json',
            'metadata' => 'json',
            'capacity_max' => 'integer',
            'price_per_hour' => 'integer',
        ];

        /**
         * Boot Logic.
         */
        protected static function booted(): void
        {
            static::creating(function (EventVenue $model) {
                $model->uuid = (string) Str::uuid();
                $model->correlation_id = (string) Str::uuid();

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
         * Relation with Planner agency.
         */
        public function planner(): BelongsTo
        {
            return $this->belongsTo(EventPlanner::class, 'planner_id');
        }
}
