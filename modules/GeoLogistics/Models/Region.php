<?php declare(strict_types=1);

namespace Modules\GeoLogistics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Region extends Model
{
    use HasFactory;

    protected $table = 'regions';
    
    protected $fillable = [
        'country_id',
        'name',
        'code',
        'is_active',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
    
    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'region_id');
    }
}
