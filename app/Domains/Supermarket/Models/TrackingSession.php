<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TrackingSession extends Model
{
    protected $table = 'tracking_sessions';

    protected $fillable = [
        'order_id',
        'courier_id',
        'current_location',
        'temperature_celsius',
        'status',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'current_location' => 'array',
        'temperature_celsius' => 'decimal:2',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(SupermarketOrder::class, 'order_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'courier_id');
    }

    public function telemetry(): HasMany
    {
        return $this->hasMany(TrackingTelemetry::class, 'tracking_session_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('ended_at');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
