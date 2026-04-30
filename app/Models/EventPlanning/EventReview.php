<?php

declare(strict_types=1);

namespace App\Models\EventPlanning;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class EventReview extends Model
{
    protected $table = 'event_reviews';

    protected $fillable = [
        'uuid', 'correlation_id', 'tenant_id', 'planner_id', 'client_id', 'rating', 'comment', 'media',
    ];

    protected $casts = [
        'media' => 'json',
        'rating' => 'integer',
    ];

    /**
     * Planner reviewed.
     */
    public function planner(): BelongsTo
    {
        return $this->belongsTo(EventPlanner::class, 'planner_id');
    }

    /**
     * Boot Logic.
     */
    protected static function booted(): void
    {
        self::creating(function (EventReview $model) {
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

        self::addGlobalScope('tenant', function ($query) {
            if ($this->guard->check()) {
                $query->where('tenant_id', $this->guard->user()?->tenant_id);
            }
        });
    }
}
