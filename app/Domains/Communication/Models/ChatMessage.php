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

    protected $table = 'chat_messages';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'chat_room_id',
        'uuid',
        'correlation_id',
        'sender_user_id',
        'is_system_message',
        'type',
        'body',
        'attachment_path',
        'attachment_meta',
        'inline_data',
        'is_read',
        'read_at',
        'delivery_status',
        'reply_to_message_id',
        'is_deleted',
        'deleted_at',
        'reactions',
        'tags',
    ];

    protected $casts = [
        'attachment_meta'  => 'array',
        'inline_data'      => 'array',
        'reactions'        => 'array',
        'tags'             => 'array',
        'is_read'          => 'boolean',
        'is_system_message'=> 'boolean',
        'is_deleted'       => 'boolean',
        'read_at'          => 'datetime',
        'deleted_at'       => 'datetime',
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
