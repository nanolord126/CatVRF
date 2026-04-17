<?php

declare(strict_types=1);

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

/**
 * Векторное представление (embedding) профиля пользователя для ML-рекомендаций
 *
 * @package App\Models
 */
final class UserEmbedding extends Model
{

    protected $table = 'user_embeddings';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'vertical',
        'embedding',
        'model_version',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'embedding' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        // tenant global scope добавляется в сервисах через ->where('tenant_id', ...)
    }
}
