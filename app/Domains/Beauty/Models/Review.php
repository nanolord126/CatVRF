<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * КАНОН 2026: Beauty Review Model (Layer 2)
 */
final class Review extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'beauty_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'salon_id',
        'master_id',
        'appointment_id',
        'rating',
        'comment',
        'photos',
        'correlation_id',
    ];

    protected $casts = [
        'rating' => 'integer',
        'photos' => 'json',
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

    public function salon(): BelongsTo
    {
        return $this->belongsTo(BeautySalon::class, 'salon_id');
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class, 'master_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }
}
