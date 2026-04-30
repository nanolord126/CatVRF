<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TableReservationModel extends Model
{
    use HasFactory;

    protected $table = 'table_reservations';

    protected $fillable = [
        'tenant_id',
        'table_id',
        'user_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'guest_count',
        'reservation_time',
        'arrival_time',
        'completed_at',
        'cancelled_at',
        'status',
        'special_requests',
    ];

    protected $casts = [
        'guest_count' => 'integer',
        'reservation_time' => 'datetime',
        'arrival_time' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTableModel::class, 'table_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed', 'arrived']);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('reservation_time', '>=', now());
    }

    public function scopePastDue($query)
    {
        return $query->where('reservation_time', '<', now())
            ->whereIn('status', ['pending', 'confirmed']);
    }
}
