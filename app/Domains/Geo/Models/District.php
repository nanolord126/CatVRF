<?php

namespace App\Domains\Geo\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class District extends GeoRoot {
    public function region(): BelongsTo { return $this->belongsTo(Region::class); }
    public function cities(): HasMany { return $this->hasMany(City::class); }
}
