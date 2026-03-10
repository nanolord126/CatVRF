<?php

namespace App\Models\Analytics;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumerBehaviorLog extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $fillable = [
        'user_id',
        'event_type',
        'entity_type',
        'entity_id',
        'payload',
        'correlation_id'
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}








