<?php

declare(strict_types=1);

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\SoftDeletes;

/**
 * Юрист/Адвокат (alias App\Domains\Legal\Models\Lawyer)
 *
 * @package App\Models
 */
final class Lawyer extends Model
{
use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'lawyers';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'user_id',
        'full_name',
        'specialization',
        'bar_number',
        'experience_years',
        'rating',
        'hourly_rate',
        'is_active',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'rating' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // tenant global scope добавляется в сервисах через ->where('tenant_id', ...)
    }
}
