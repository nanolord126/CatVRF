<?php

declare(strict_types=1);

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

/**
 * Вертикаль Legal (юридические услуги, агрегатор)
 *
 * @package App\Models
 */
final class Legal extends Model
{

    protected $table = 'legal';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'correlation_id',
        'name',
        'type',
        'status',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // tenant global scope добавляется в сервисах через ->where('tenant_id', ...)
    }
}
