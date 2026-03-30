<?php declare(strict_types=1);

namespace App\Domains\Freelance\TranslationServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TranslationJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'translation_jobs';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'translator_id',
            'client_id',
            'correlation_id',
            'status',
            'source_language',
            'target_language',
            'word_count',
            'price',
            'deadline',
            'submitted_at',
            'tags',
            'meta',
        ];

        protected $casts = [
            'deadline' => 'datetime',
            'submitted_at' => 'datetime',
            'tags' => 'json',
            'meta' => 'json',
            'price' => 'integer',
            'word_count' => 'integer',
        ];
    }


        protected $casts = [
            'total_kopecks' => 'integer',
            'payout_kopecks' => 'integer',
            'word_count' => 'integer',
            'delivery_date' => 'datetime',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('translation_jobs.tenant_id', tenant()->id));
        }
}
