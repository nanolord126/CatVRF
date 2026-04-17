<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class TaxiDispatcherQueue extends Model
{
    use HasFactory;

    protected $table = 'taxi_dispatcher_queue';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'ride_id',
        'driver_id',
        'status',
        'priority',
        'assigned_at',
        'accepted_at',
        'declined_at',
        'timeout_at',
        'decline_reason',
        'correlation_id',
        'metadata',
        'tags'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'timeout_at' => 'datetime',
        'priority' => 'integer',
        'metadata' => 'json',
        'tags' => 'json',
    ];

    protected $hidden = ['metadata'];

    /**
     * Статусы очереди диспетчера.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_TIMEOUT = 'timeout';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Приоритеты.
     */
    public const PRIORITY_LOW = 1;
    public const PRIORITY_NORMAL = 2;
    public const PRIORITY_HIGH = 3;
    public const PRIORITY_URGENT = 4;

    protected static function booted(): void
    {
        static::creating(function (TaxiDispatcherQueue $queue) {
            $queue->uuid = $queue->uuid ?? (string) Str::uuid();
            $queue->tenant_id = $queue->tenant_id ?? (tenant()->id ?? 1);
            $queue->status = $queue->status ?? self::STATUS_PENDING;
            $queue->priority = $queue->priority ?? self::PRIORITY_NORMAL;
            $queue->assigned_at = $queue->assigned_at ?? now();
            $queue->correlation_id = $queue->correlation_id ?? (request()->header('X-Correlation-ID') ?? (string) Str::uuid());
        });

        static::addGlobalScope('tenant', function ($query) {
            if (tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Отношения.
     */
    public function ride(): BelongsTo
    {
        return $this->belongsTo(TaxiRide::class, 'ride_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    /**
     * Проверить, ожидает ли назначения.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Проверить, назначен ли водителю.
     */
    public function isAssigned(): bool
    {
        return $this->status === self::STATUS_ASSIGNED;
    }

    /**
     * Проверить, принят ли водителем.
     */
    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Проверить, отклонен ли водителем.
     */
    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    /**
     * Проверить, истекло ли время ожидания.
     */
    public function isTimeout(): bool
    {
        return $this->status === self::STATUS_TIMEOUT;
    }

    /**
     * Проверить, истекло ли время ожидания.
     */
    public function hasTimedOut(): bool
    {
        return $this->timeout_at && $this->timeout_at->isPast() && !$this->isAccepted();
    }

    /**
     * Пометить как назначенный.
     */
    public function markAsAssigned(int $driverId): void
    {
        $this->update([
            'driver_id' => $driverId,
            'status' => self::STATUS_ASSIGNED,
            'assigned_at' => now(),
            'timeout_at' => now()->addSeconds(30), // 30 seconds to accept
        ]);
    }

    /**
     * Пометить как принятый.
     */
    public function markAsAccepted(): void
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Пометить как отклоненный.
     */
    public function markAsDeclined(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_DECLINED,
            'declined_at' => now(),
            'decline_reason' => $reason,
        ]);
    }

    /**
     * Пометить как timeout.
     */
    public function markAsTimeout(): void
    {
        $this->update([
            'status' => self::STATUS_TIMEOUT,
        ]);
    }
}
