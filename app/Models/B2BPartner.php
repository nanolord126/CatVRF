<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Bavix\Wallet\Traits\HasWallet;
use Bavix\Wallet\Interfaces\Wallet;

/**
 * B2B Partner - Corporate Client or Travel Agency
 */
class B2BPartner extends Model implements Wallet
{
    use SoftDeletes, HasWallet;

    protected $table = 'b2b_partners';

    protected $fillable = [
        'name',
        'inn',
        'kpp',
        'legal_address',
        'email',
        'phone',
        'correlation_id',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(B2BContract::class, 'partner_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(B2BOrder::class, 'partner_id');
    }

    public function getActiveContract(): ?B2BContract
    {
        return $this->contracts()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('end_date')
                      ->orWhereDate('end_date', '>=', now());
            })
            ->first();
    }
}










