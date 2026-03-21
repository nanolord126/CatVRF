<?php declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Бронь мойки авто.
 * Production 2026.
 */
final class CarWashBooking extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'car_wash_bookings';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'wash_type',
        'box_number',
        'status',
        'scheduled_at',
        'completed_at',
        'price',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'tags' => 'collection',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'price' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(related: \App\Models\User::class, foreignKey: 'client_id');
    }
}
