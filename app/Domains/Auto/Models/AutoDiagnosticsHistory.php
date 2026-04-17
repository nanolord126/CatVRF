<?php declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class AutoDiagnosticsHistory extends Model
{
    use SoftDeletes;

    protected $table = 'auto_diagnostics_history';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'vehicle_id',
        'user_id',
        'uuid',
        'correlation_id',
        'diagnostics_data',
        'tags',
    ];

    protected $casts = [
        'diagnostics_data' => 'json',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            $query->where('tenant_id', tenant()->id ?? 0);
        });

        static::creating(function (Model $model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
            if (!$model->tenant_id) {
                $model->tenant_id = tenant()->id ?? 0;
            }
        });
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(AutoVehicle::class, 'vehicle_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function getVehicleAttribute(): ?array
    {
        return $this->diagnostics_data['vehicle'] ?? null;
    }
}
