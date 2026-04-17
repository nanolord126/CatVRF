<?php

declare(strict_types=1);

namespace App\Domains\Sports\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LiveStreamParticipant extends Model
{
    use HasFactory;

    protected $table = 'sports_stream_participants';

    protected $fillable = [
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

    public function stream(): BelongsTo
    {
        return $this->belongsTo(LiveStream::class, 'stream_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\LiveStreamParticipantFactory::new();
    }
}
