<?php

namespace Modules\Bonuses\Models;

use Illuminate\Database\Eloquent\Model;

class BonusProgram extends Model
{
    protected $fillable = [
        'name',
        'type', // cashback, fixed, discount
        'value',
        'is_active',
        'owner_id',
        'owner_type',
    ];

    public function owner()
    {
        return $this->morphTo();
    }
}
