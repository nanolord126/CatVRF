<?php declare(strict_types=1);

namespace Modules\GeoLogistics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class City extends Model
{
    use HasFactory;

    protected $table = 'cities';
    
    protected $fillable = [
        'region_id',
        'name',
        'code',
        'is_active',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }
}
