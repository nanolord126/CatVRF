<?php declare(strict_types=1);

namespace App\Models\EventPlanning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EventVenue extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;

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
         * Relation with Planner agency.
         */
        public function planner(): BelongsTo
        {
            return $this->belongsTo(EventPlanner::class, 'planner_id');
        }
}
