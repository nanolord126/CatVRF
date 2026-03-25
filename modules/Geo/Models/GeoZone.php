declare(strict_types=1);

<?php

namespace Modules\Geo\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * GeoZone
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class GeoZone extends Model
{
    protected $fillable = ['name', 'type', 'coordinates'];
    protected $casts = ['coordinates' => 'array'];
}
