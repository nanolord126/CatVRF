<?php declare(strict_types=1);

namespace Modules\GeoLogistics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Country extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
