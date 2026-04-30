<?php

declare(strict_types=1);

namespace App\Domains\Travel\SubVerticals\Hotels\Models;

use Request;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class HotelBooking extends Model
{
    use TenantScoped;

    protected $table = 'hotels_bookings';

    protected $fillable = [
        'tenant_id', 'hotel_id', 'room_id', 'customer_id', 'uuid', 'correlation_id',
        'check_in', 'check_out', 'total_price', 'status', 'guests_count',
        'special_requests', 'payment_status',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'total_price' => 'decimal:2',
        'guests_count' => 'integer',
    ];

    public function __construct(
        private readonly Request $request,
    ) {}

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function (Builder $query): void {
            if (app()->bound('tenant') && app('tenant') instanceof Tenant) {
                $query->where('tenant_id', app('tenant')->id);
            }
        });

        self::creating(function (Model $model): void {
            if (! $model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
            if (! $model->correlation_id) {
                $model->correlation_id = $this->request /* TODO: inject via constructor DI */ /* TODO: inject via DI */->header('X-Correlation-ID', (string) Str::uuid());
            }
        });
    }
}
