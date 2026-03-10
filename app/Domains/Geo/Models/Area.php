<?php

namespace App\Domains\Geo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Area extends GeoRoot {
    public function city(): BelongsTo { return $this->belongsTo(City::class); }
}
