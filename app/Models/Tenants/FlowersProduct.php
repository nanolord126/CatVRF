<?php

namespace App\Models\Tenants;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;
use App\Models\AuditLog;
use Illuminate\Support\Str;

class FlowersProduct extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $table = 'flowers_products';

    protected $fillable = [
        'name', 'description', 'price', 'stock_quantity', 
        'composition', 'photo_url', 'is_active', 'correlation_id'
    ];

    protected $casts = [
        'composition' => 'array',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(fn ($model) => $model->correlation_id ??= Str::uuid());
        
        static::updated(function ($model) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'updated',
                'auditable_type' => get_class($model),
                'auditable_id' => $model->id,
                'metadata' => ['changes' => $model->getChanges()],
                'correlation_id' => $model->correlation_id,
            ]);
        });
    }
}








