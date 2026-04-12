<?php

declare(strict_types=1);

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\SoftDeletes;

/**
 * Юридическая услуга (каталог услуг для AILegalAdvisorConstructor)
 *
 * @package App\Models
 */
final class LegalService extends Model
{
use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'legal_services';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'name',
        'type',
        'description',
        'price',
        'duration_minutes',
        'is_active',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        // tenant global scope добавляется в сервисах через ->where('tenant_id', ...)
    }
}
