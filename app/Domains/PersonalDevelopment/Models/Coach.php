<?php declare(strict_types=1);

namespace App\Domains\PersonalDevelopment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Coach extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'pd_coaches';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'name',
        'bio',
        'specializations',
        'rating',
        'hourly_rate_kopecks',
        'is_active',
        'correlation_id',
        'tags',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'specializations' => 'json',
        'tags' => 'json',
        'is_active' => 'boolean',
        'rating' => 'float',
        'hourly_rate_kopecks' => 'integer',
        'tenant_id' => 'integer',
    ];

    /**
     * Booted method for global scoping and UUID generation.
     */
    protected static function booted(): void
    {
        // Изоляция данных на уровне базы (Tenant Scoping)
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()?->id) {
                $builder->where('tenant_id', tenant()?->id);
            }
        });

        // Автогенерация UUID и Correlation ID
        static::creating(function (Coach $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) Str::uuid();
            }
            if (empty($model->tenant_id) && function_exists('tenant')) {
                $model->tenant_id = (int) tenant()?->id;
            }
        });
    }

    /**
     * Пользователь, связанный с ролью коуча.
     */
    public function user(): BelongsTo
    {
        /** @var \App\Models\User $userModel */
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Программы, закрепленные за коучем.
     */
    public function programs(): HasMany
    {
        return $this->hasMany(Program::class, 'coach_id');
    }

    /**
     * Индивидуальные сессии.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'coach_id');
    }

    /**
     * Полиморфные отзывы о коуче.
     */
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Метод для расчета цены сессии.
     */
    public function calculateSessionPrice(int $durationMinutes): int
    {
        return (int) (($this->hourly_rate_kopecks / 60) * $durationMinutes);
    }
}
