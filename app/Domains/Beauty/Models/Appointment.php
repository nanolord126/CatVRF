<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class Appointment extends Model
{
    protected $table = 'beauty_appointments';

    protected $fillable = [
        'tenant_id',
        'salon_id',
        'master_id',
        'service_id',
        'user_id',
        'uuid',
        'correlation_id',
        'status',
        'starts_at',
        'ends_at',
        'total_price',
        'is_b2b',
        'cancellation_reason'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'total_price' => 'decimal:2',
        'is_b2b' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id ?? 1);
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function salon(): BelongsTo { return $this->belongsTo(Salon::class); }
    public function master(): BelongsTo { return $this->belongsTo(Master::class); }
    public function service(): BelongsTo { return $this->belongsTo(BeautyService::class); }
}
