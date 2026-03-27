<?php

declare(strict_types=1);

namespace App\Domains\Education\LanguageLearning\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Модель Языковой Школы по канону 2026.
 *
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property string $name
 * @property string|null $description
 * @property array $languages
 * @property bool $is_verified
 * @property array|null $settings
 * @property string|null $correlation_id
 * @property array|null $tags
 */
final class LanguageSchool extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'language_schools';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'description',
        'address',
        'languages',
        'is_verified',
        'settings',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'languages' => 'json',
        'settings' => 'json',
        'tags' => 'json',
        'is_verified' => 'boolean',
    ];

    /**
     * Автоматическая генерация UUID и tenant_id.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (int) (tenant('id') ?? 1);
        });

        static::addGlobalScope('tenant_id', function ($query) {
            if (tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    /**
     * Преподаватели этой школы.
     */
    public function teachers(): HasMany
    {
        return $this->hasMany(LanguageTeacher::class, 'school_id');
    }

    /**
     * Курсы этой школы.
     */
    public function courses(): HasMany
    {
        return $this->hasMany(LanguageCourse::class, 'school_id');
    }

    /**
     * Получить метаданные школы (расширенный формат).
     */
    public function getMetadataAttribute(): array
    {
        return [
            'total_teachers' => $this->teachers()->count(),
            'active_courses' => $this->courses()->count(),
            'languages_count' => count($this->languages ?? []),
        ];
    }
}
