<?php

namespace App\Models\Tenants;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;
use App\Models\User;

class HRExchangeOffer extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $fillable = ['user_id', 'role_code', 'hourly_rate', 'status'];
    protected $casts = ['hourly_rate' => 'decimal:2'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}








