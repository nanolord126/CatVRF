<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Traits\HasWallet;

class GiftCard extends Model implements Wallet
{
    use BelongsToTenant, HasWallet;

    protected $fillable = ['tenant_id', 'code', 'amount', 'fee', 'status', 'created_by', 'activated_by'];
    protected $casts = ['amount' => 'decimal:2', 'fee' => 'decimal:2'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function activator() { return $this->belongsTo(User::class, 'activated_by'); }
}









