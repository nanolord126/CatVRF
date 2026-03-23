<?php

declare(strict_types=1);

namespace App\Domains\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\User;

final class Message extends Model
{
    use SoftDeletes;

    protected $table = 'chat_messages';

    protected $fillable = [
        'uuid',
        'conversation_id',
        'sender_id',
        'content',
        'type',
        'payload',
        'correlation_id',
    ];

    protected $casts = [
        'payload' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->uuid = $m->uuid ?? (string) Str::uuid());
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
