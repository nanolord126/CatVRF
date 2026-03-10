<?php

namespace App\Domains\Geo\Models;

use App\Traits\Common\{HasEcosystemFeatures, HasEcosystemAuth};
use Illuminate\Database\Eloquent\{Model, Relations\HasMany, Relations\BelongsTo};

class GeoRoot extends Model
{
    use HasEcosystemFeatures, HasEcosystemAuth;
    protected $guarded = [];
}
