<?php

namespace Modules\Hotels\Models;

use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    protected $fillable = ['name', 'stars', 'category', 'address', 'latitude', 'longitude'];
}
