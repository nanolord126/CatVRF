<?php

declare(strict_types=1);

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

/**
 * Вертикаль Commerce (живая коммерция, агрегатор товаров)
 *
 * @package App\Models
 */
final class Commerce extends Model
{

    protected $table = 'commerce';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'correlation_id',
        'name',
        'status',
        'type',
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
