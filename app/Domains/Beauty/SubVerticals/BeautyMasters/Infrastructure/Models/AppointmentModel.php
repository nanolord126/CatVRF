<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class AppointmentModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'beauty_appointments';

    protected $fillable = [
        'venue_id',
        'master_id',
        'client_id',
        'service_id',
        'start_time',
        'end_time',
        'status',
        'price',
        'discount_amount',
        'final_price',
        'currency',
        'payment_status',
        'payment_id',
        'notes',
        'client_notes',
        'is_online_booking',
        'booking_source',
        'confirmed_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'reminder_sent_24h',
        'reminder_sent_2h',
        'metadata',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'is_online_booking' => 'boolean',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'reminder_sent_24h' => 'integer',
        'reminder_sent_2h' => 'integer',
        'metadata' => 'array',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class);
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(MasterModel::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ClientModel::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceModel::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Payment::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(AppointmentPhotoModel::class);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>=', now())
            ->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopePast($query)
    {
        return $query->where('end_time', '<', now());
    }

    public function scopeByMaster($query, int $masterId)
    {
        return $query->where('master_id', $masterId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
