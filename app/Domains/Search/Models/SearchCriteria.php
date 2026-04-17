<?php

declare(strict_types=1);

namespace App\Domains\Search\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * SearchCriteria — модель поисковых критериев для всех вертикалей.
 *
 * Разделяет критерии на:
 * - Публичные: индексируются в поиске, доступны для фильтрации
 * - Непубличные: публичный критерий, но строго привязан к конкретной вертикали
 *
 * Пример: фильтр "BMW" доступен только для Auto вертикали,
 * не может появиться в Beauty (маникюр).
 *
 * @package App\Domains\Search\Models
 * @version 2026.1
 */
final class SearchCriteria extends Model
{
    protected $table = 'search_criteria';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'vertical',
        'code',
        'name',
        'name_ru',
        'description',
        'type', // 'public' | 'vertical_restricted'
        'data_type', // 'boolean' | 'integer' | 'string' | 'decimal' | 'json'
        'is_indexed', // индексируется в поиске
        'is_filterable', // доступен для фильтрации пользователями
        'is_required', // обязательный критерий
        'sort_order',
        'options', // JSON с опциями для select/radio
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'is_indexed' => 'boolean',
        'is_filterable' => 'boolean',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
        'options' => 'json',
        'metadata' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }

            if (empty($model->code) && !empty($model->name)) {
                $model->code = Str::slug($model->name);
            }
        });

        static::addGlobalScope('tenant', function ($builder): void {
            $builder->where('search_criteria.tenant_id', tenant()->id);
        });
    }

    /**
     * Получить критерии для конкретной вертикали.
     */
    public function scopeForVertical($query, string $vertical)
    {
        return $query->where('vertical', $vertical)
            ->orWhere('type', 'public');
    }

    /**
     * Получить только индексируемые критерии.
     */
    public function scopeIndexed($query)
    {
        return $query->where('is_indexed', true);
    }

    /**
     * Получить только фильтруемые критерии.
     */
    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }

    /**
     * Получить строковое представление модели.
     */
    public function __toString(): string
    {
        return sprintf(
            '%s[id=%s, vertical=%s, code=%s, type=%s]',
            static::class,
            $this->id ?? 'new',
            $this->vertical ?? 'all',
            $this->code ?? '',
            $this->type ?? 'public',
        );
    }
}
