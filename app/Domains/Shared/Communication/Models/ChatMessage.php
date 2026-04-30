<?php

declare(strict_types=1);

namespace App\Domains\Communication\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Models\Tenant;
use App\Models\User;

/**
 * Chat message inside a room.
 * Tenant-scoped.
 */
final class ChatMessage extends Model
{
    use TenantScoped;

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
        'is_system_message' => 'boolean',
        'is_deleted'       => 'boolean',
        'read_at'          => 'datetime',
        'deleted_at'       => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class, 'room_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', static function (Builder $query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
