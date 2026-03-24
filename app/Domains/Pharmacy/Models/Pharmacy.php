<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class Pharmacy extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'pharmacies';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'correlation_id',
        'name',
        'owner_id',
        'description',
        'address',
        'phone',
        'latitude',
        'longitude',
        'license_number',
        'license_issuer',
        'license_document',
        'is_verified',
        'commission_percent',
        'has_cold_chain',
        'schedule',
        'tags',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'has_cold_chain' => 'boolean',
        'commission_percent' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
        'schedule' => 'json',
        'tags' => 'json',
    ];

    public function medicines()
    {
        return $this->hasMany(Medicine::class, 'pharmacy_id');
    }

    public function orders()
    {
        return $this->hasMany(PharmacyOrder::class, 'pharmacy_id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('pharmacies.tenant_id', tenant()->id);
        });
    }
}
