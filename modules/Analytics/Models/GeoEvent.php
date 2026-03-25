declare(strict_types=1);

<?php

namespace Modules\Analytics\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * GeoEvent
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class GeoEvent extends Model
{
    protected $fillable = ['type', 'lat', 'lng', 'intensity'];
}
