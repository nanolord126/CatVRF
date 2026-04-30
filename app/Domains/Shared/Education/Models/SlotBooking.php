<?php

declare(strict_types=1);

namespace App\Domains\Education\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BusinessGroup;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

final class SlotBooking extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'education_slot_bookings';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'user_id',
        'slot_id',
        'booking_reference',
        'status',
        'booked_at',
        'confirmed_at',
        'cancelled_at',
        'attended_at',
        'biometric_hash',
        'device_fingerprint',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'booked_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'attended_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed']);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'], true)
            && $this->slot->start_time->gt(CarbonImmutable::now()->addHours(2));
    }

    public function markAsAttended(): void
    {
        $this->update([
            'status' => 'completed',
            'attended_at' => CarbonImmutable::now(),
        ]);
    }

    public function markAsNoShow(): void
    {
        $this->update([
            'status' => 'no_show',
        ]);
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) Str::uuid();
            }
        });
    }
}
