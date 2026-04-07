<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\Tenant;
use App\Models\BusinessGroup;

final class Salon extends Model
{
    protected $table = 'beauty_salons';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'correlation_id',
        'name',
        'address',
        'lat',
        'lon',
        'status',
        'tags',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'tags' => 'json',
        'metadata' => 'json',
        'is_active' => 'boolean',
        'lat' => 'float',
        'lon' => 'float',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            // Non-facade tenant resolving 
            // Real system uses dependency or contextual function tenant()
            $query->where('tenant_id', tenant()->id ?? 1);
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class, 'business_group_id');
    }

    public function masters(): HasMany
    {
        return $this->hasMany(Master::class, 'salon_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(BeautyService::class, 'salon_id');
    }
}
