<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\\HasEcosystemTracing;

use App\Traits\StrictTenantIsolation;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Attendance extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    use StrictTenantIsolation;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'clock_in_geo',
        'clock_out_geo',
        'status',
        'total_hours',
        'correlation_id',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'clock_in_geo' => 'array',
        'clock_out_geo' => 'array',
        'total_hours' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}








