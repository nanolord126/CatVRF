<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = ['owner_id', 'owner_type', 'balance', 'currency'];

    public function owner()
    {
        return $this->morphTo();
    }
}
