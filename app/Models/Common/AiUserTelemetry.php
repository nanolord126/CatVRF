<?php

namespace App\Models\Common;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;
use App\Models\User;

/**
 * AI User Telemetry for Behavioral Tracking & Recommendation Engine.
 */
class AiUserTelemetry extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    public $timestamps = false; // Uses created_at timestamp in migration

    protected $table = 'ai_user_telemetry';

    protected $fillable = [
        'user_id',
        'event_type',
        'entity_type',
        'entity_id',
        'category',
        'payload',
        'correlation_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}








