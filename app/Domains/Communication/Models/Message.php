<?php

declare(strict_types=1);

namespace App\Domains\Communication\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * In-app / cross-channel message.
 * Tenant-scoped.
 */
final class Message extends Model
{
use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'communication_messages';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'correlation_id',
        'channel_id',
        'sender_id',
        'recipient_id',
        'recipient_type',   // user | business_group | broadcast
        'channel_type',     // email | sms | push | in_app | telegram
        'subject',
        'body',
        'status',           // pending | sent | delivered | failed | read
        'metadata',
        'tags',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'metadata'     => 'array',
        'tags'         => 'array',
        'sent_at'      => 'datetime',
        'delivered_at' => 'datetime',
        'read_at'      => 'datetime',
        'failed_at'    => 'datetime',
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

    public function sender(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'recipient_id');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(CommunicationChannel::class, 'channel_id');
    }
}
