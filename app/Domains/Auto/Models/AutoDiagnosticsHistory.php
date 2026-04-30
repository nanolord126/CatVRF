<?php

declare(strict_types=1);

namespace App\Domains\Auto\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use Illuminate\Support\Str;

final class AutoDiagnosticsHistory extends Model
{
    use SoftDeletes;
    use TenantScoped;

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

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(AutoVehicle::class, 'vehicle_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getVehicleAttribute(): ?array
    {
        return $this->diagnostics_data['vehicle'] ?? null;
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function (Builder $query) {
            $query->where('tenant_id', tenant()->id ?? 0);
        });

        self::creating(function (Model $model) {
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
            if (! $model->tenant_id) {
                $model->tenant_id = tenant()->id ?? 0;
            }
        });
    }
}
