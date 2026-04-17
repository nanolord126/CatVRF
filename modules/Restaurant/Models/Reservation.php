<?php declare(strict_types=1);

namespace Modules\Restaurant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'restaurant_reservations';

    protected $fillable = [
        'tenant_id',
        'restaurant_id',
        'user_id',
        'uuid',
        'reservation_date',
        'reservation_time',
        'party_size',
        'status',
        'special_requests',
        'contact_phone',
        'contact_email',
        'confirmation_code',
        'is_confirmed',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'reservation_time' => 'datetime',
        'party_size' => 'integer',
        'is_confirmed' => 'boolean',
        'special_requests' => 'json',
        'metadata' => 'json',
    ];

    protected $hidden = ['deleted_at'];

    /**
     * Статусы бронирования.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_NO_SHOW = 'no_show';

    /**
     * Global scope для tenant scoping.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant_scoped', function ($query) {
            if ($tenantId = tenant('id')) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    /**
     * Получить ресторан.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    /**
     * Получить пользователя.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
