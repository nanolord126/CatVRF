<?php

namespace App\Models\Tenants;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use App\Models\User;
use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Traits\HasWallet;
use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxiDriverProfile extends Model implements Wallet
{
    use HasWallet;

    protected $fillable = [
        'user_id',
        'fleet_id',
        'current_car_id',
        'license_number',
        'rating',
        'is_online',
        'current_geo',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'rating' => 'decimal:2',
        'current_geo' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(TaxiFleet::class, 'fleet_id');
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(TaxiCar::class, 'current_car_id');
    }
}








