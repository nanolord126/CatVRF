<?php

declare(strict_types=1);

namespace App\Domains\Sports\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Database\Factories\LiveStreamParticipantFactory;

final class LiveStreamParticipant extends Model
{
    use HasFactory;

    protected $table = 'sports_stream_participants';

    protected $fillable = [
        'tenant_id',
        'stream_id',
        'user_id',
        'participant_token',
        'joined_at',
        'left_at',
        'correlation_id',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('tenant') && app('tenant') instanceof \App\Models\Tenant) {
                $query->where('tenant_id', app('tenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(LiveStream::class, 'stream_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory()
    {
        return LiveStreamParticipantFactory::new();
    }
}
