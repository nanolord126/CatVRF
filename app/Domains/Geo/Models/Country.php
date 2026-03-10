<?php

namespace App\Domains\Geo\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends GeoRoot {
    public function regions(): HasMany { return $this->hasMany(Region::class); }
}
