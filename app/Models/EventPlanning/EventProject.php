<?php declare(strict_types=1);

namespace App\Models\EventPlanning;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class EventProject extends Model
{
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
