<?php

declare(strict_types=1);

namespace App\Domains\Communication\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Models\Tenant;

/**
 * Chat room / conversation.
 * Tenant-scoped.
 */
final class ChatRoom extends Model
{
    use TenantScoped;

    protected $table = 'chat_rooms';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'correlation_id',
        'type',
        'context_type',
        'context_id',
        'vertical',
        'title',
        'participants',
        'created_by_user_id',
        'messages_count',
        'last_message_at',
        'status',
        'closed_at',
        'tags',
    ];

    protected $casts = [
        'participants'   => 'array',
        'tags'           => 'array',
        'last_message_at' => 'datetime',
        'closed_at'      => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'room_id');
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
