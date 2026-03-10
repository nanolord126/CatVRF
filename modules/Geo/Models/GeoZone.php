<?php

namespace Modules\Geo\Models;

use Illuminate\Database\Eloquent\Model;

class GeoZone extends Model
{
    protected $fillable = ['name', 'type', 'coordinates'];
    protected $casts = ['coordinates' => 'array'];
}
