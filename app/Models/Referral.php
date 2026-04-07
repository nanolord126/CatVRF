<?php declare(strict_types=1);

namespace App\Models;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Реферальная ссылка и отношение (Referral)
 *
 * КАНОН 2026 - Production Ready
 * Хранит информацию о реферальных ссылках и привлечённых пользователях
 *
 * Статусы:
 * - active: ссылка активна, готова к использованию
 * - registered: приглашённый зарегистрировался
 * - qualified: достигнута минимальная трата для бонуса
 * - rewarded: бонус выплачен
 * - expired: ссылка истекла
 * - inactive: деактивирована пользователем
 */
final class Referral extends Model
{
    protected $table = 'referrals';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'referrer_id',
        'referee_id',
        'tenant_id',
        'referral_code',
        'status',
        'source_platform',
        'migrated_at',
        'registered_at',
        'rewarded_at',
        'bonus_amount',
        'correlation_id',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'bonus_amount' => 'integer',
        'registered_at' => 'datetime',
        'rewarded_at' => 'datetime',
        'migrated_at' => 'datetime',
        'tags' => 'json',
        'metadata' => 'json',
    ];

    /**
     * Global scope: tenant scoping
     */
    protected static function booted()
    {
        static::addGlobalScope('tenant', function ($query) {
            if ($this->guard->check() && $this->guard->user()->tenant_id) {
                $query->where('tenant_id', $this->guard->user()->tenant_id);
            }
        });
    }

    /**
     * Relations
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'referrer_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'referee_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeRegistered($query)
    {
        return $query->where('status', 'registered');
    }

    public function scopeRewarded($query)
    {
        return $query->where('status', 'rewarded');
    }
}
