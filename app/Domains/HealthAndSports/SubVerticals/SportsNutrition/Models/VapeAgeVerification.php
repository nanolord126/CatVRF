<?php

declare(strict_types=1);

namespace App\Domains\HealthAndSports\SubVerticals\SportsNutrition\Models;

use Carbon\CarbonImmutable;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\User;

final class VapeAgeVerification extends Model
{
    use TenantScoped;

    protected $table = 'vapes_age_verifications';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'method',
        'external_id',
        'status',
        'birth_date',
        'verified_at',
        'provider_response',
        'correlation_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'verified_at' => 'datetime',
        'provider_response' => 'json',
        'tenant_id' => 'integer',
        'user_id' => 'integer',
    ];

    protected $hidden = [
        'id',
        'provider_response',
    ];

    /**
     * Владелец верификации.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Проверка: является ли пользователь совершеннолетним.
     */
    public function isVerifiedAdult(): bool
    {
        return $this->status === 'verified' &&
               ($this->birth_date?->diffInYears(CarbonImmutable::now()) >= 18);
    }

    /**
     * Booted method for global scoping and UUID generation.
     */
    protected static function booted(): void
    {
        // Изоляция данных на уровне базы (Tenant Scoping Канон 2026)
        self::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()?->id) {
                $builder->where('tenant_id', (int) tenant()?->id);
            }
        });

        // Автогенерация UUID и Correlation ID
        self::creating(function (VapeAgeVerification $model) {
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
}
