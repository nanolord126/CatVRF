<?php

namespace App\Domains\Geo\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Region extends GeoRoot {
    public function country(): BelongsTo { return $this->belongsTo(Country::class); }
    public function districts(): HasMany { return $this->hasMany(District::class); }
}
