<?php declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ServiceWarranty extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_warranties';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'auto_service_order_id',
        'client_id',
        'vehicle_id',
        'warranty_type',
        'warranty_months',
        'warranty_km',
        'start_date',
        'end_date',
        'start_mileage',
        'status',
        'warranty_number',
        'claim_date',
        'claim_reason',
        'claim_status',
        'claim_mileage',
        'repair_description',
        'notes',
        'uuid',
        'correlation_id',
        'tags',
    ];

    protected $hidden = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'claim_date' => 'datetime',
        'warranty_months' => 'integer',
        'warranty_km' => 'integer',
        'start_mileage' => 'integer',
        'claim_mileage' => 'integer',
        'tags' => 'json',
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

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(AutoServiceOrder::class, 'auto_service_order_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'client_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' 
            && $this->end_date >= now()->toDateString();
    }

    public function isExpired(): bool
    {
        return $this->end_date < now()->toDateString();
    }

    public function isValidByMileage(?int $currentMileage = null): bool
    {
        if (!$this->warranty_km || !$currentMileage) {
            return true;
        }

        return ($currentMileage - $this->start_mileage) <= $this->warranty_km;
    }
}
