declare(strict_types=1);

<?php

namespace Modules\Hotels\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Hotel
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class Hotel extends Model
{
    protected $fillable = ['name', 'stars', 'address', 'latitude', 'longitude'];
}
