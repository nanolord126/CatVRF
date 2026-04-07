<?php declare(strict_types=1);

namespace App\Domains\PersonalDevelopment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Review extends Model
{
    use HasFactory;

    protected $table = 'pd_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'reviewable_id',
        'reviewable_type',
        'user_id',
        'rating',
        'comment',
        'correlation_id',
    ];

    protected $casts = [
        'rating' => 'integer',
        'user_id' => 'integer',
        'tenant_id' => 'integer',
        'reviewable_id' => 'integer',
    ];

    /**
     * Booted method for global scoping and automatic UUID generation.
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
        static::creating(function (Review $model) {
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

        // Обновление рейтинга коуча или программы при создании отзыва
        static::created(function (Review $model) {
            $model->updateTargetRating();
        });
    }

    /**
     * Полиморфное отношение к цели отзыва.
     */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Пользователь, оставивший отзыв.
     */
    public function user(): BelongsTo
    {
        /** @var \App\Models\User $userModel */
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Логика обновления рейтинга цели отзыва.
     */
    public function updateTargetRating(): void
    {
        $target = $this->reviewable;
        if ($target instanceof Coach || $target instanceof Program) {
            $avg = self::where('reviewable_type', get_class($target))
                ->where('reviewable_id', $target->id)
                ->avg('rating');

            $target->update(['rating' => $avg ?? 5.00]);
        }
    }
}
