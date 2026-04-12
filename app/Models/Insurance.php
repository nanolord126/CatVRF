<?php

declare(strict_types=1);

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\SoftDeletes;

/**
 * Вертикаль Insurance (страхование)
 *
 * @package App\Models
 */
final class Insurance extends Model
{
use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'insurances';

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
