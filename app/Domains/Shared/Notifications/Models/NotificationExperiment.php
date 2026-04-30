<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class NotificationExperiment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'event_type',
        'variant_a',
        'variant_b',
        'traffic_percent',
        'is_active',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'variant_a' => 'array',
        'variant_b' => 'array',
        'traffic_percent' => 'integer',
        'is_active' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationExperimentLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('started_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('ended_at')->orWhere('ended_at', '>', now());
            });
    }
}
