<?php declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class VehicleInspection extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_inspections';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'vehicle_id',
        'client_id',
        'inspection_type',
        'scheduled_at',
        'completed_at',
        'status',
        'result',
        'notes',
        'inspector_id',
        'price',
        'payment_status',
        'certificate_number',
        'expires_at',
        'uuid',
        'correlation_id',
        'tags',
    ];

    protected $hidden = [];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'price' => 'integer',
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

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'client_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'inspector_id');
    }
}
