<?php

declare(strict_types=1);

namespace App\Domains\Sports\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class LiveStream extends Model
{
    use HasFactory;

    protected $table = 'sports_live_streams';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'user_id',
        'trainer_id',
        'session_title',
        'session_description',
        'scheduled_start',
        'scheduled_end',
        'stream_type',
        'max_participants',
        'current_participants',
        'status',
        'webrtc_room',
        'stream_token',
        'started_at',
        'ended_at',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'tags' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(LiveStreamParticipant::class, 'stream_id');
    }

    protected static function newFactory()
    {
        return \Database\Factories\LiveStreamFactory::new();
    }
}
