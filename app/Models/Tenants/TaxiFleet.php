<?php

namespace App\Models\Tenants;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Traits\HasWallet;
use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxiFleet extends Model implements Wallet
{
    use HasWallet;

    protected $fillable = [
        'name',
        'address',
        'commission_rate',
        'correlation_id',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
    ];

    public function cars(): HasMany
    {
        return $this->hasMany(TaxiCar::class, 'fleet_id');
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(TaxiDriverProfile::class, 'fleet_id');
    }
}








