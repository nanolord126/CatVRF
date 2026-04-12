<?php

declare(strict_types=1);

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\SoftDeletes;

/**
 * Версия ML-модели прогнозирования спроса
 *
 * @package App\Models
 */
final class DemandModelVersion extends Model
{
use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'demand_model_versions';

    protected $fillable = [
        'version',
        'vertical',
        'trained_at',
        'accuracy',
        'precision_score',
        'recall',
        'f1_score',
        'auc_roc',
        'file_path',
        'is_active',
        'comment',
    ];

    protected $casts = [
        'trained_at' => 'datetime',
        'accuracy' => 'decimal:4',
        'precision_score' => 'decimal:4',
        'recall' => 'decimal:4',
        'f1_score' => 'decimal:4',
        'auc_roc' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // tenant global scope добавляется в сервисах через ->where('tenant_id', ...)
    }
}
