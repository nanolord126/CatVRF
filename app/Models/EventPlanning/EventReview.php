<?php

declare(strict_types=1);

namespace App\Models\EventPlanning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * EventReview Model (Client Feedback).
 * Implementation: Layer 2 (Domain/Database Layer).
 */
final class EventReview extends Model
{
    use HasFactory;

    protected $table = 'event_reviews';

    protected $fillable = [
        'uuid', 'correlation_id', 'tenant_id', 'planner_id', 'client_id', 'rating', 'comment', 'media',
    ];

    protected $casts = [
        'media' => 'json',
        'rating' => 'integer',
    ];

    /**
     * Boot Logic.
     */
    protected static function booted(): void
    {
        static::creating(function (EventReview $model) {
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
     * Planner reviewed.
     */
    public function planner(): BelongsTo
    {
        return $this->belongsTo(EventPlanner::class, 'planner_id');
    }
}
