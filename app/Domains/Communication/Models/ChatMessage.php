<?php

declare(strict_types=1);

namespace App\Domains\Communication\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Chat message inside a room.
 * Tenant-scoped.
 */
final class ChatMessage extends Model
{
use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'communication_chat_messages';

    protected $fillable = [
        'tenant_id',
        'room_id',
        'uuid',
        'correlation_id',
        'sender_id',
        'body',
        'type',         // text | image | file | system
        'attachment_url',
        'is_read',
        'metadata',
        'tags',
    ];

    protected $casts = [
        'metadata' => 'array',
        'tags'     => 'array',
        'is_read'  => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function (Builder $query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class, 'room_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'sender_id');
    }
}
