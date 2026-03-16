<?php

declare(strict_types=1);

namespace Modules\GeoLogistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Country extends Model
{
    protected $table = 'countries';

    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class, 'country_id');
    }
}
