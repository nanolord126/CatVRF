<?php

declare(strict_types=1);

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\SoftDeletes;

/**
 * Enterprise клиент (крупные B2B-заказчики)
 *
 * @package App\Models
 */
final class Enterprise extends Model
{
use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'enterprises';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'correlation_id',
        'name',
        'inn',
        'kpp',
        'legal_address',
        'credit_limit',
        'status',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'credit_limit' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // tenant global scope добавляется в сервисах через ->where('tenant_id', ...)
    }
}
