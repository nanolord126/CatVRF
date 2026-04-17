<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TaxiWebrtcCall extends Model
{
    use HasFactory;

    protected $table = 'taxi_webrtc_calls';

    protected $fillable = [
        'uuid',
        'ride_id',
        'tenant_id',
        'business_group_id',
        'caller_id',
        'callee_id',
        'status',
        'signaling_key',
        'initiated_at',
        'accepted_at',
        'ended_at',
        'ended_by',
        'end_reason',
        'duration_seconds',
        'expires_at',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'ride_id' => 'integer',
        'tenant_id' => 'integer',
        'business_group_id' => 'integer',
        'caller_id' => 'integer',
        'callee_id' => 'integer',
        'initiated_at' => 'datetime',
        'accepted_at' => 'datetime',
        'ended_at' => 'datetime',
        'ended_by' => 'integer',
        'duration_seconds' => 'integer',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'correlation_id',
        'signaling_key',
    ];

    public function ride(): BelongsTo
    {
        return $this->belongsTo(TaxiRide::class, 'ride_id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }
}
