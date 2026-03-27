<?php

declare(strict_types=1);


namespace App\Domains\Sports\Fitness\Models;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final /**
 * Membership
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class Membership extends Model
{
    use SoftDeletes;

    protected $table = 'memberships';
    protected $fillable = ['tenant_id', 'gym_id', 'member_id', 'type', 'amount', 'commission_amount', 'started_at', 'expires_at', 'status', 'cancellation_reason', 'auto_renewal', 'transaction_id', 'classes_included', 'classes_used', 'correlation_id'];
    protected $casts = [
        'amount' => 'float',
        'commission_amount' => 'float',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'auto_renewal' => 'boolean',
        'classes_included' => 'integer',
        'classes_used' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id')));
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'member_id', 'member_id');
    }
}
