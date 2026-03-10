<?php

namespace Modules\Analytics\Models;

use Illuminate\Database\Eloquent\Model;

class GeoEvent extends Model
{
    protected $fillable = ['type', 'lat', 'lng', 'intensity'];
}
