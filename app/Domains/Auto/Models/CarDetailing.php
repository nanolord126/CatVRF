<?php declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CarDetailing extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'car_detailing';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'client_id',
        'vehicle_id',
        'service_type',
        'scheduled_at',
        'duration_minutes',
        'price',
        'status',
        'payment_status',
        'notes',
        'uuid',
        'correlation_id',
        'tags',
    ];

    protected $hidden = [];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'price' => 'integer',
        'duration_minutes' => 'integer',
        'tags' => 'json',
        'service_type' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (auth()->check() && tenancy()->initialized) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'client_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
