<?php

declare(strict_types=1);

namespace App\Domains\Communication\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Chat room / conversation.
 * Tenant-scoped.
 */
final class ChatRoom extends Model
{
use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'communication_chat_rooms';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'correlation_id',
        'type',         // support | b2b_deal | order | direct
        'title',
        'entity_type',  // order | booking | deal | null
        'entity_id',
        'is_active',
        'metadata',
        'tags',
    ];

    protected $casts = [
        'metadata'  => 'array',
        'tags'      => 'array',
        'is_active' => 'boolean',
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

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'room_id');
    }
}
