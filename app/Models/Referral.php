<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Referral extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $fillable = ['referrer_id', 'referred_id', 'type', 'milestone_turnover', 'bonus_paid_50k'];

    public function referrer() { return $this->belongsTo(User::class, 'referrer_id'); }
    public function referred() { return $this->belongsTo(User::class, 'referred_id'); }
}









