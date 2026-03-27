<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * КАНОН 2026: Beauty Appointment Model (Layer 2)
 */
final class Appointment extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'beauty_appointments';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'user_id',
        'salon_id',
        'master_id',
        'service_id',
        'datetime_start',
        'datetime_end',
        'status',
        'price',
        'payment_status',
        'client_comment',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'datetime_start' => 'datetime',
        'datetime_end' => 'datetime',
        'price' => 'integer',
        'tags' => 'json',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_scoping', function ($builder) {
            if (function_exists('tenant') && tenant('id')) {
                $builder->where('tenant_id', tenant('id'));
            }
        });
    }

    /**
     * Relationships
     */
    public function salon(): BelongsTo
    {
        return $this->belongsTo(BeautySalon::class, 'salon_id');
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class, 'master_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Beauty\Models\BeautyService::class, 'service_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }
}
