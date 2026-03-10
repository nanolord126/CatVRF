<?php

namespace App\Domains\Geo\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends GeoRoot {
    public function district(): BelongsTo { return $this->belongsTo(District::class); }
    public function areas(): HasMany { return $this->hasMany(Area::class); }
}
