<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\\HasEcosystemTracing;

use App\Traits\StrictTenantIsolation;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Audit Log based on Spatie Activitylog - customized for B2B "Canon"
 */
final class AuditLog extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    use StrictTenantIsolation;
    // Usually uses the default activity_log table, but we can wrap it if needed.
    // However, the instructions ask for "correlation_id" support.
}








