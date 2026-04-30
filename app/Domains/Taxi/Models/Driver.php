<?php

declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant;
use App\Models\BusinessGroup;
use Illuminate\Support\Str;

final class Driver extends Model
{
    use TenantScoped;

    protected $table = 'taxi_drivers';

    protected $fillable = [
        'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
        'first_name', 'last_name', 'license_number', 'phone_number',
        'rating', 'is_active', 'is_available', 'current_lat', 'current_lon', 'documents', 'metadata',
    ];

    protected $casts = [
        'documents' => 'json',
        'metadata' => 'json',
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'rating' => 'decimal:2',
        'current_lat' => 'decimal:8',
        'current_lon' => 'decimal:8',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    public function rides(): HasMany
    {
        return $this->hasMany(Ride::class);
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function (Builder $query): void {
            if (app()->bound('tenant') && app('tenant') instanceof Tenant) {
                $query->where('tenant_id', app('tenant')->id);
            }
        });

        self::creating(function (Model $model): void {
            if (! $model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
            if (! $model->correlation_id) {
                $model->correlation_id = request()->header('X-Correlation-ID', (string) Str::uuid());
            }
        });
    }
}
