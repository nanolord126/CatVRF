<?php declare(strict_types=1);

namespace App\Models\EventPlanning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EventProject extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes;

        protected $table = 'events_projects';

        /**
         * Mass assignment.
         */
        protected $fillable = [
            'uuid',
            'correlation_id',
            'tenant_id',
            'planner_id',
            'client_id',
            'title',
            'theme',
            'event_date',
            'guest_count',
            'status',
            'type', // b2b / b2c
            'metadata',
            'tags',
        ];

        /**
         * Casting for JSONB and dates.
         */
        protected $casts = [
            'metadata' => 'json',
            'tags' => 'json',
            'event_date' => 'datetime',
            'guest_count' => 'integer',
        ];

        /**
         * Boot logic for UUID and Tenant Scoping.
         */
        protected static function booted(): void
        {
            static::creating(function (EventProject $model) {
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
         * Relation with Planner.
         */
        public function planner(): BelongsTo
        {
            return $this->belongsTo(EventPlanner::class, 'planner_id');
        }

        /**
         * Relation with Bookings (financial).
         */
        public function bookings(): HasMany
        {
            return $this->hasMany(EventBooking::class, 'event_id');
        }

        /**
         * Relation with Client (User model placeholder).
         */
        public function client(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'client_id');
        }
}
