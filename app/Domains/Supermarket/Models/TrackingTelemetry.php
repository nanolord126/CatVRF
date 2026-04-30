<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TrackingTelemetry extends Model
{
    protected $table = 'tracking_telemetry';

    protected $fillable = [
        'tracking_session_id',
        'location',
        'temperature_celsius',
        'speed_kmh',
        'recorded_at',
    ];

    protected $casts = [
        'location' => 'array',
        'temperature_celsius' => 'decimal:2',
        'speed_kmh' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function trackingSession(): BelongsTo
    {
        return $this->belongsTo(TrackingSession::class, 'tracking_session_id');
    }

    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('recorded_at', 'desc')->limit($limit);
    }
}
