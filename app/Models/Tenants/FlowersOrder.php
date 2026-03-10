<?php

namespace App\Models\Tenants;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Str;

class FlowersOrder extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $table = 'flowers_orders';

    protected $fillable = [
        'user_id', 'total_amount', 'delivery_fee', 'delivery_address', 
        'delivery_geo', 'status', 'payment_status', 'correlation_id'
    ];

    protected $casts = [
        'delivery_geo' => 'array',
        'total_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::creating(fn ($model) => $model->correlation_id ??= Str::uuid());
        
        static::updated(function ($model) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'status_changed',
                'auditable_type' => get_class($model),
                'auditable_id' => $model->id,
                'metadata' => ['status' => $model->status],
                'correlation_id' => $model->correlation_id,
            ]);
        });
    }
}








