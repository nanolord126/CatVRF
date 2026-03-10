<?php

namespace App\Domains\Beauty\Models;

use App\Traits\HasAuditLog;
use App\Traits\HasEcosystemFeatures;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Salon extends Model
{
    use HasAuditLog, HasEcosystemFeatures;

    protected $fillable = [
        'tenant_id', 'owner_id', 'name', 'description',
        'address', 'latitude', 'longitude', 'phone', 'status', 'services'
    ];

    protected $casts = [
        'services' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
