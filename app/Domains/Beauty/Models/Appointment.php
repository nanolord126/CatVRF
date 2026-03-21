<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель записи на услугу.
 * Production 2026.
 */
final class Appointment extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'appointments';

    protected $fillable = [
        'tenant_id',
        'salon_id',
        'master_id',
        'service_id',
        'client_id',
        'datetime_start',
        'status',
        'price',
        'payment_status',
        'correlation_id',
        'tags',
        'metadata',
    ];

    protected $hidden = [];

    protected $casts = [
        'datetime_start' => 'datetime',
        'tags' => 'collection',
        'metadata' => 'json',
        'price' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(BeautySalon::class, 'salon_id');
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class, 'master_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(BeautyService::class, 'service_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(related: \App\Models\User::class, foreignKey: 'client_id');
    }

    public function consumables(): HasMany
    {
        return $this->hasMany(BeautyConsumable::class, 'appointment_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'appointment_id');
    }
}
