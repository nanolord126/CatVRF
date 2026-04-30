<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Client — клиент бьюти-салона.
 * 
 * Хранит информацию о клиенте с историей посещений, предпочтениями,
 * аллергиями и фотографиями "до/после".
 */
final class Client extends Model
{
    use TenantScoped;

    protected $table = 'beauty_clients';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'date_of_birth',
        'gender',
        'notes',
        'allergies',
        'preferences',
        'loyalty_points',
        'total_visits',
        'total_spent',
        'last_visit_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'last_visit_at' => 'datetime',
        'preferences' => 'json',
        'loyalty_points' => 'integer',
        'total_visits' => 'integer',
        'total_spent' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(AppointmentPhoto::class);
    }

    /**
     * Обновить статистику посещений
     */
    public function updateVisitStats(float $amount): void
    {
        $this->increment('total_visits');
        $this->increment('total_spent', $amount);
        $this->update(['last_visit_at' => now()]);
    }

    /**
     * Начислить баллы лояльности
     */
    public function addLoyaltyPoints(int $points): void
    {
        $this->increment('loyalty_points', $points);
    }

    /**
     * Списать баллы лояльности
     */
    public function redeemLoyaltyPoints(int $points): bool
    {
        if ($this->loyalty_points < $points) {
            return false;
        }

        $this->decrement('loyalty_points', $points);
        return true;
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id ?? 1);
        });
    }
}
