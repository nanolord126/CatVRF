<?php

declare(strict_types=1);

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

/**
 * Событие Mesh/WebRTC (используется в MeshService для broadcast-комнат)
 *
 * @package App\Models
 */
final class Event extends Model
{

    protected $table = 'events';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'user_id',
        'room_id',
        'type',
        'payload',
        'status',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'payload' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        // tenant global scope добавляется в сервисах через ->where('tenant_id', ...)
    }
}
