<?php

declare(strict_types=1);

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\SoftDeletes;

/**
 * Вертикаль EventManagement (организация мероприятий)
 *
 * @package App\Models
 */
final class EventManagement extends Model
{
use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'event_managements';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'correlation_id',
        'name',
        'type',
        'status',
        'starts_at',
        'ends_at',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // tenant global scope добавляется в сервисах через ->where('tenant_id', ...)
    }
}
