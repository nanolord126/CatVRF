<?php

namespace Modules\Beauty\Models;

use Illuminate\Database\Eloquent\Model;

class BeautySalon extends Model
{
    protected $fillable = ['name', 'category', 'address', 'latitude', 'longitude'];
}
